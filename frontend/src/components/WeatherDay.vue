<template>
    <div class="vitals">
        <div class="temperature">
            <p v-if="high || low">
                <span v-if="high" class="high">{{ Math.round(high) }} </span>
                <span v-if="high && low"> / </span>
                <span v-if="low" class="low">{{ Math.round(low) }} </span>
            </p>
        </div>
    </div>
    <p>{{ description }}</p>
</template>

<script>
export default {
    name: "WeatherDay",
    props: {
        high: String,
        low: String,
        description: String,
        date: Date,
        icon: String
    }
}
</script>

<style scoped>

.vitals p {
    margin-top: 0;
    margin-left: 20px;
}
.high {
    font-size: 2.5rem;
}
</style>
