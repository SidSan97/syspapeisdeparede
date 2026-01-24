<template>
    <div
        class="trello-card"
        :draggable="true"
        @dragstart="$emit('drag-start', $event)"
        @click="$emit('click')"
    >
        <div v-if="coverImage" class="trello-card-image">
            <img :src="coverImage" :alt="card.name" />
        </div>
        <div class="trello-card-footer">
            <div class="trello-card-footer-content">
                <span class="trello-card-footer-text">{{ displayName }}</span>
                <div class="trello-card-footer-meta">
                    <div class="trello-card-deadline">
                        <i class="fa fa-clock-o"></i>
                        <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                    </div>
                    <div v-if="commentsCount > 0" class="trello-card-comment-count">
                        <i class="fa fa-comment"></i>
                        <span>{{ commentsCount }}</span>
                    </div>
                    <div v-if="activitiesCount > 0" class="trello-card-activity-count">
                        <i class="fa fa-list"></i>
                        <span>{{ activitiesCount }}</span>
                    </div>
                    <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="trello-card-attachment-count">
                        <i class="fa fa-paperclip"></i>
                        <span>{{ card.uploaded_files.length }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    card: {
        type: Object,
        required: true,
    },
    coverImage: {
        type: String,
        default: '',
    },
    displayName: {
        type: String,
        required: true,
    },
    commentsCount: {
        type: Number,
        default: 0,
    },
    activitiesCount: {
        type: Number,
        default: 0,
    },
});

defineEmits(['drag-start', 'click']);
</script>

<style scoped>
.trello-card {
    background-color: var(--bs-card-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    box-shadow: var(--bs-box-shadow-sm);
    transition: all 0.2s ease;
    user-select: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.trello-card:hover {
    box-shadow: var(--bs-box-shadow);
    transform: translateY(-2px);
}

.trello-card:active {
    cursor: grabbing;
}

.trello-card-image {
    width: 100%;
    height: 150px;
    overflow: hidden;
    background-color: var(--bs-secondary-bg);
    flex-shrink: 0;
}

.trello-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.trello-card-footer {
    background-color: var(--ds-surface-sunken);
    color: var(--ds-text);
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
    min-height: 36px;
}

.trello-card-footer-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    width: 100%;
}

.trello-card-footer-text {
    font-size: 0.75rem;
    color: var(--ds-text);
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.trello-card-footer-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.trello-card-deadline {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6875rem;
    color: var(--ds-text);
}

.trello-card-deadline i {
    color: var(--ds-text);
    font-size: 0.6875rem;
}

.trello-card-comment-count,
.trello-card-activity-count,
.trello-card-attachment-count {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.6875rem;
    color: var(--ds-text);
}

.trello-card-comment-count i,
.trello-card-activity-count i,
.trello-card-attachment-count i {
    color: var(--ds-text);
    font-size: 0.6875rem;
}

.trello-card-comment-count span,
.trello-card-activity-count span,
.trello-card-attachment-count span {
    font-weight: 500;
}
</style>
