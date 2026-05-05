<script setup>
import { ref, computed, nextTick } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'save']);

const showInput = ref(false);
const inputRef = ref(null);

const canSave = computed(() => props.modelValue.trim().length > 0);

const toggle = () => (showInput.value = !showInput.value);

const open = async () => {
  showInput.value = true;
  await nextTick();
  inputRef.value?.focus();
};

const save = () => {
  if (canSave.value) {
    emit('save', props.modelValue);
    toggle();
  }
};

const onInput = (event) => {
  emit('update:modelValue', event.target.value);
};

const onKeyupEsc = (event) => {
  if (event.key === 'Escape') {
    toggle();
  }
};
</script>

<template>
  <button
    v-show="!showInput"
    @click="open"
    type="button"
    class="btn btn-subtle w-100 text-start bg-body rounded-3 shadow"
  >
    Escrever um comentário...
  </button>

  <div v-show="showInput">
    <textarea
      ref="inputRef"
      class="form-control mb-2"
      placeholder="Escrever um comentário..."
      maxlength="500"
      :value="props.modelValue"
      @input="onInput"
      @keyup="onKeyupEsc"
    ></textarea>

    <button type="button" class="btn btn-default" @click="save" :disabled="!canSave">Salvar</button>
  </div>
</template>
