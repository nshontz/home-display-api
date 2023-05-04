<template>
    <div class="weather">
        <div class="temperature">
            <p>{{ description }}</p>
            <p v-if="current">{{ current }}</p>
            <p v-if="high || low">
                <span v-if="high">{{ high }} </span>
                <span v-if="high && low"> / </span>
                <span v-if="low">{{ low }} </span>
            </p>
        </div>
        <div class="icon" v-bind:style="{ 'background-image': 'url(' + icon + ')' }"></div>

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
    height: 75px;
    background-size: contain;
    background-position: center center;
    background-repeat: no-repeat;
}

.weather {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.weather p {
    margin-top: 0;
    margin-left: 20px;
}
</style>
