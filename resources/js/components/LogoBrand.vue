<template>
    <span class="logo-brand" :style="{ fontSize: size }">
        visa<span class="logo-brand-accent">bor</span><span class="logo-brand-dot"></span>
    </span>
</template>

<script setup>
defineProps({
    size: { type: String, default: '1.25rem' }
});
</script>

<style scoped>
.logo-brand {
    font-family: 'Sora', 'Inter', system-ui, sans-serif;
    font-weight: 800;
    color: #0A1F44;
    letter-spacing: -0.02em;
    display: inline-flex;
    align-items: center;
    gap: 0;
    user-select: none;
}
.logo-brand-accent {
    color: #1BA97F;
}
.logo-brand-dot {
    width: 6px;
    height: 6px;
    background: #1BA97F;
    border-radius: 50%;
    margin-left: 4px;
    flex-shrink: 0;
}
</style>
