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
- Identifica o nosso Order via `metadata.order_id` ou `[order_ref:ID]` na descrição do item
- Atualiza `orders.paid = 1` quando o pagamento é confirmado
- Implementa idempotência (não reprocessa se já pago)
- Retorna sempre `200` para o Pagar.me

> ⚠️ **Importante:** retornar `200` é obrigatório. Se o seu serviço não conseguir receber o webhook, o Pagar.me tentará reenviar conforme o número de tentativas configurado. Se você retornar 500 ou não responder, ele vai retentar automaticamente.

---

## 4. Testar localmente via Postman

Para simular o webhook no ambiente local:

1. **URL:** `POST http://seudominio/api/webhook/pagarme`
2. **Headers:** `Content-Type: application/json`
3. **Body (raw JSON):** use a estrutura abaixo, ajustando `data.metadata.order_id` ou `data.items[0].description` com o ID de um pedido existente no banco (`paid = 0`):

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

> Troque `"1"` em `metadata.order_id` e `[order_ref:1]` pelo ID real de um pedido na tabela `orders` com `paid = 0` para ver a atualização.

---

## 5. Configurar o Webhook no Painel do Pagar.me

No painel, vá em **Configurações → Webhooks → Criar Webhook**, informe a URL para onde as notificações serão enviadas e selecione os eventos desejados.

Para detectar pagamento de link, selecione pelo menos:

| Evento | Descrição |
|---|---|
| `order.paid` ✅ | Pedido pago com sucesso |
| `order.payment_failed` | Falha no pagamento (opcional) |
| `order.canceled` | Pedido cancelado (opcional) |

> 📘 **Portas suportadas:** `http:80` e `https:443`

---

## 6. Estrutura do Payload Recebido

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

## 7. Atributos do Objeto Webhook

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

