<template>
    <div class="card mb-2 production-card" :draggable="!isFullyProduced" @dragstart="$emit('drag-start', $event)"
        @click="$emit('click')">
        <div v-if="coverImage" class="production-card-cover flex-shrink-0 bg-light">
            <img
                :src="coverImage"
                :alt="card.name"
                class="production-card-cover-img"
            />
        </div>
        <div class="card-body px-3 pt-3 pb-2 flex-grow-1 min-w-0">
            <div class="mb-2">
                <div class="d-flex gap-2 align-items-start flex-wrap">
                    <p class="fs-sm mb-0 flex-grow-1">{{ displayName }}</p>

                    <span
                        v-if="card.status"
                        class="badge text-wrap align-self-start flex-shrink-0"
                        :class="getOrderBudgetStatusBadgeClass(card.status)"
                    >{{ card.status }}</span>
                </div>
                <span v-if="!isFullyProduced && productionTimerText" class="d-inline-block mt-1">
                    <span
                        class="badge text-wrap production-deadline-badge"
                        :class="productionTimerClass"
                    >{{ productionTimerText }}</span>
                </span>
            </div>
            <div class="d-flex gap-3">
                <div class="fa-xs text-body-secondary">
                    <i class="far fa-clock me-2"></i>
                    <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                </div>

                <div v-if="commentsCount > 0" class="fa-xs text-body-secondary">
                    <i class="far fa-comment me-2"></i>
                    <span>{{ commentsCount }}</span>
                </div>

                <div v-if="activitiesCount > 0" class="fa-xs text-body-secondary">
                    <i class="fa fa-list me-2"></i>
                    <span>{{ activitiesCount }}</span>
                </div>

                <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="fa-xs text-body-secondary">
                    <i class="fa fa-paperclip me-2"></i>
                    <span>{{ card.uploaded_files.length }}</span>
                </div>
            </div>
            <div v-if="card.production_date" class="mt-2">
                <span class="fa-xs text-body-secondary">Produção: {{ formattedProductionDate }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { getOrderBudgetStatusBadgeClass } from '@/utils/cardUtils';

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
    formattedProductionDate: {
        type: String,
        default: '',
    },
    productionTimerText: {
        type: String,
        default: '',
    },
    productionTimerClass: {
        type: String,
        default: '',
    },
    isFullyProduced: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['drag-start', 'click']);
</script>

<style scoped>
.production-card {
    --bs-card-border-radius: var(--bs-border-radius-lg, 8px);
    box-shadow: var(--ds-shadow-raised);
    cursor: pointer;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.production-card-cover {
    width: 100%;
    overflow: hidden;
    border-radius: var(--bs-card-border-radius, 8px) var(--bs-card-border-radius, 8px) 0 0;
}

.production-card-cover-img {
    display: block;
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.production-deadline-badge {
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    max-width: 100%;
}

.production-deadline-badge--success {
    background-color: #198754;
}

.production-deadline-badge--warning {
    background-color: #fd7e14;
}

.production-deadline-badge--danger {
    background-color: #dc3545;
}

.production-card:hover {
    transform: translateY(-2px);
}

.production-card:active {
    cursor: grabbing;
}

.production-card-body {
    /* background-color: var(--ds-surface-sunken); */
    color: var(--ds-text);
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
    min-height: 36px;
}

.production-card-body-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    width: 100%;
}

.production-card-body-text {
    /* font-size: 0.75rem;
    color: var(--ds-text);
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap; */
}

.production-card-body-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.production-card-deadline {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6875rem;
    color: var(--ds-text);
}

.production-card-deadline i {
    color: var(--ds-text);
    font-size: 0.6875rem;
}

.production-card-comment-count,
.production-card-activity-count,
.production-card-attachment-count {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.6875rem;
    color: var(--ds-text);
}

.production-card-comment-count i,
.production-card-activity-count i,
.production-card-attachment-count i {
    color: var(--ds-text);
    font-size: 0.6875rem;
}

.production-card-comment-count span,
.production-card-activity-count span,
.production-card-attachment-count span {
    font-weight: 500;
}

.production-card-production-date {
    font-size: 0.6875rem;
    color: var(--ds-text);
    margin-top: 0.25rem;
    padding-top: 0.25rem;
    border-top: 1px solid var(--ds-background-accent-gray-bolder-hovered);
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.production-card-timer {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.6875rem;
    font-weight: 500;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
}

.production-card-timer i {
    font-size: 0.6875rem;
}

.production-card-timer span {
    font-weight: 500;
}

.production-card-timer-normal {
    color: var(--ds-text);
    background-color: var(--ds-background-accent-gray-subtlest-pressed);
}

.production-card-timer-normal i {
    /* color: var(--ds-text); */
}

.production-card-timer-urgent {
    color: #ffc107;
    background-color: rgba(255, 193, 7, 0.2);
}

.production-card-timer-urgent i {
    color: #ffc107;
}

.production-card-timer-overdue {
    color: #dc3545;
    background-color: rgba(220, 53, 69, 0.2);
}

.production-card-timer-overdue i {
    color: #dc3545;
}
</style>
