<template>
    <div class="weather-day">
        <div class="icon" v-bind:style="{ 'background-image': 'url(' + icon + ')' }"></div>
        <p>{{ description }}</p>
        <p v-if="current">{{ current }}</p>
        <p v-if="high && low">{{ high }} / {{ low }}</p>
    </div>
</template>

<script>
export default {
    name: "WeatherDay",
    props: {
        high: Number,
        low: Number,
        current: Number,
        description: String,
        date: Date,
        icon: String
    }
}
</script>

<style scoped>

.icon {
    height: 50px;
    background-size: contain;
    background-position: center center;
    background-repeat: no-repeat;
}
</style>