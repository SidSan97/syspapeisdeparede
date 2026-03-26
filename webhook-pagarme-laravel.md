# Detectando Pagamento via Webhook no Laravel (Pagar.me)

## Visão Geral do Fluxo

```
Cliente paga o link → Pagar.me processa → POST para sua URL →
Laravel recebe → checa type == "order.paid" → executa lógica → retorna 200
```

---

## 1. O Evento que Você Precisa Escutar

O evento-chave é o **`order.paid`**. Quando um pedido é pago, o Pagar.me dispara um `HTTP POST` para a sua URL com um payload contendo:

- `"type": "order.paid"`
- Os dados do pedido dentro do campo `data`, incluindo `"status": "paid"`

---

## 2. Rota no Laravel

Em `routes/api.php` (já implementado):

```php
Route::post('webhook/pagarme', [WebhookController::class, 'handlePagarme']);
```

URL do webhook: `POST https://seudominio.com/api/webhook/pagarme`

> Nota: Rotas da API no Laravel não utilizam CSRF por padrão, então não é necessário adicionar exceção.

---

## 3. Controller (implementado)

O `WebhookController` em `app/Http/Controllers/WebhookController.php`:

- Escuta o evento `order.paid`
- Identifica o nosso `Order` via `metadata.order_id` (ou fallback por `[order_ref:ID]` em `items[].description`)
- Tenta localizar o link de pagamento em `order_payment_links` por `data.id` (campo `external_order_id`)
- Se nao localizar por `data.id`, tenta por `metadata.internal_payment_link_id`
- Se ainda nao localizar, usa fallback para o ultimo link `pending` do pedido
- Atualiza o link encontrado para `status = paid` e preenche `paid_at`
- Recalcula o status do pedido:
  - `payment_status = unpaid` (nada pago)
  - `payment_status = partial` (pagamento parcial)
  - `payment_status = paid` (pedido quitado)
- Mantem `orders.paid` como flag de compatibilidade (`1` apenas quando quitado)
- Retorna sempre `200` para o Pagar.me

> ⚠️ **Importante:** retornar `200` é obrigatório. Se o seu serviço não conseguir receber o webhook, o Pagar.me tentará reenviar conforme o número de tentativas configurado. Se você retornar 500 ou não responder, ele vai retentar automaticamente.

---

## 4. Testar localmente via Postman

Para simular o webhook no ambiente local:

1. **URL:** `POST http://seudominio/api/webhook/pagarme`
2. **Headers:** `Content-Type: application/json`
3. **Body (raw JSON):** use a estrutura abaixo, ajustando `data.metadata.order_id` com o ID de um pedido existente no banco.

> Importante no fluxo atual: idealmente o pedido ja deve ter ao menos 1 registro em `order_payment_links` (gerado pela tela/endpoint de link).
> 
> O webhook tenta primeiro casar `data.id` com `order_payment_links.external_order_id`.
> Se nao encontrar, aplica fallback automatico (ultimo `pending` do pedido).

```json
{
  "id": "hook_RyEKQO789TRpZjv5",
  "account": {
    "id": "acc_jZkdN857et650oNv",
    "name": "Lojinha"
  },
  "type": "order.paid",
  "created_at": "2017-06-29T20:23:47",
  "data": {
    "id": "or_ZdnB5BBCmYhk534R",
    "code": "1303724",
    "amount": 12356,
    "currency": "BRL",
    "closed": true,
    "status": "paid",
    "metadata": {
      "order_id": "1"
    },
    "items": [
      {
        "id": "oi_EqnMMrbFgBf0MaN1",
        "description": "Produto [order_ref:1]",
        "amount": 10166,
        "quantity": 1,
        "status": "active"
      }
    ],
    "customer": {
      "id": "cus_oy23JRQCM1cvzlmD",
      "name": "FABIO",
      "email": "abc@teste.com"
    },
    "charges": [
      {
        "id": "ch_d22356Jf4WuGr8no",
        "status": "paid",
        "payment_method": "credit_card"
      }
    ]
  }
}
```
## OBS: Itens, customer e charges são opcionais.

### Se tudo ocorrer bem, o json de retorno esperado é:

```json
{
  "received": true
}
```

> Troque `"1"` em `metadata.order_id` pelo ID real do pedido.
> 
> Dica: se quiser testar o "casamento direto" do link, envie em `data.id` o valor salvo em `order_payment_links.external_order_id`.

### Exemplo minimo (funciona para testes locais)

```json
{
  "type": "order.paid",
  "data": {
    "id": "or_teste_postman_123",
    "status": "paid",
    "amount": 12356,
    "metadata": {
      "order_id": "303"
    }
  }
}
```

---

## 5. O que validar no banco depois do webhook

1. Em `order_payment_links`:
   - `status = paid`
   - `paid_at` preenchido
2. Em `orders`:
   - `payment_status = partial` quando pagamento parcial
   - `payment_status = paid` quando quitado total
   - `paid = 1` somente quando quitado total

---

## 6. Configurar o Webhook no Painel do Pagar.me

No painel, vá em **Configurações → Webhooks → Criar Webhook**, informe a URL para onde as notificações serão enviadas e selecione os eventos desejados.

Para detectar pagamento de link, selecione pelo menos:

| Evento | Descrição |
|---|---|
| `order.paid` ✅ | Pedido pago com sucesso |
| `order.payment_failed` | Falha no pagamento (opcional) |
| `order.canceled` | Pedido cancelado (opcional) |

> 📘 **Portas suportadas:** `http:80` e `https:443`

---

## 7. Estrutura do Payload Recebido

```json
{
  "id": "hook_RyEKQO789TRpZjv5",
  "type": "order.paid",
  "account": {
    "id": "acc_xxx",
    "name": "Sua Loja"
  },
  "data": {
    "id": "or_ZdnB5BBCmYhk534R",
    "status": "paid",
    "amount": 12356,
    "customer": {
      "name": "João Silva",
      "email": "joao@email.com"
    },
    "items": []
  }
}
```

---

## 8. Atributos do Objeto Webhook

| Atributo | Tipo | Descrição |
|---|---|---|
| `id` | string | Código do webhook. Formato: `hook_XXXXXXXXXXXXXXXX` |
| `url` | string | Endereço do alvo |
| `event` | enum | Evento do webhook |
| `status` | enum | `pending`, `sent` ou `failed` |
| `attempts` | string | Tentativas realizadas |
| `last_attempt` | datetime | Data da última tentativa |
| `response_status` | string | Código de resposta do servidor |
| `response_raw` | string | Resposta do servidor |
| `account` | object | Dados da loja |
| `data` | object | Conteúdo da requisição |

---

