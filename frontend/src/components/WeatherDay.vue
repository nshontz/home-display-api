<template>
    <div class="vitals">
        <div class="temperature">
            <p v-if="high || low">
                <span v-if="high" class="high">{{ Math.round(high) }} </span>
                <span v-if="high && low"> / </span>
                <span v-if="low" class="low">{{ Math.round(low) }} </span>
            </p>
        </div>
        <div class="icon" v-bind:style="{ 'background-image': 'url(' + icon + ')' }"></div>
    </div>
    <p>{{ description }}</p>
</template>

<script>
export default {
    name: "WeatherDay",
    props: {
        high: Number,
        low: Number,
        description: String,
        date: Date,
        icon: String
    }
}
</script>

<style scoped>
.icon {
    height: 60px;
    background-size: contain;
    background-position: center center;
    background-repeat: no-repeat;
}

.vitals {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.vitals p {
    margin-top: 0;
    margin-left: 20px;
}
.high {
    font-size: 2rem;
}
</style>
