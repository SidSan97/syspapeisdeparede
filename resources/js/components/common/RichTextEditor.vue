<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { IconBold, IconItalic } from '@tabler/icons-vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Digite o texto do termo…',
  },
  editable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
  content: props.modelValue || '',
  editable: props.editable,
  extensions: [
    StarterKit.configure({
      heading: false,
      bulletList: false,
      orderedList: false,
      blockquote: false,
      code: false,
      codeBlock: false,
      horizontalRule: false,
      strike: false,
    }),
  ],
  editorProps: {
    attributes: {
      class: 'rich-text-editor__content form-control',
      'data-placeholder': props.placeholder,
    },
  },
  onUpdate: ({ editor: currentEditor }) => {
    const html = currentEditor.getHTML();
    emit('update:modelValue', html === '<p></p>' ? '' : html);
  },
});

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) {
      return;
    }

    const current = editor.value.getHTML();
    const next = value || '';
    const normalizedCurrent = current === '<p></p>' ? '' : current;

    if (normalizedCurrent !== next) {
      editor.value.commands.setContent(next, { emitUpdate: false });
    }
  },
);

watch(
  () => props.editable,
  (editable) => {
    editor.value?.setEditable(editable);
  },
);

onBeforeUnmount(() => {
  editor.value?.destroy();
});

const isBoldActive = computed(() => editor.value?.isActive('bold') ?? false);
const isItalicActive = computed(() => editor.value?.isActive('italic') ?? false);

function toggleBold() {
  editor.value?.chain().focus().toggleBold().run();
}

function toggleItalic() {
  editor.value?.chain().focus().toggleItalic().run();
}
</script>

<template>
  <div class="rich-text-editor" :class="{ 'rich-text-editor--disabled': !editable }">
    <div class="rich-text-editor__toolbar btn-group" role="toolbar" aria-label="Formatação">
      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        :class="{ active: isBoldActive }"
        :disabled="!editable || !editor"
        title="Negrito"
        aria-label="Negrito"
        @click="toggleBold"
      >
        <IconBold :size="16" />
      </button>
      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        :class="{ active: isItalicActive }"
        :disabled="!editable || !editor"
        title="Itálico"
        aria-label="Itálico"
        @click="toggleItalic"
      >
        <IconItalic :size="16" />
      </button>
    </div>

    <EditorContent :editor="editor" class="rich-text-editor__surface" />
  </div>
</template>

<style scoped>
.rich-text-editor {
  border: 1px solid var(--bs-border-color);
  border-radius: var(--bs-border-radius);
  background: var(--bs-body-bg);
  overflow: hidden;
}

.rich-text-editor--disabled {
  opacity: 0.7;
}

.rich-text-editor__toolbar {
  display: flex;
  gap: 0.25rem;
  padding: 0.5rem;
  border-bottom: 1px solid var(--bs-border-color);
  background: var(--bs-tertiary-bg);
}

.rich-text-editor__surface :deep(.rich-text-editor__content) {
  min-height: 220px;
  max-height: 480px;
  overflow-y: auto;
  border: 0;
  border-radius: 0;
  box-shadow: none;
  padding: 0.75rem 1rem;
  resize: vertical;
}

.rich-text-editor__surface :deep(.rich-text-editor__content:focus) {
  box-shadow: none;
  outline: none;
}

.rich-text-editor__surface :deep(.rich-text-editor__content p) {
  margin-bottom: 0.75rem;
}

.rich-text-editor__surface :deep(.rich-text-editor__content p:last-child) {
  margin-bottom: 0;
}
</style>
