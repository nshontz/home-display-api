<template>
    <div class="dinner-item" :class="[( day.dinner && day.dinner.complete?'complete' :'')]">
        <div v-if="day.dinner" @click="toggleMeal()">
            <h2>{{ day.dinner.title }}</h2>
        </div>
        <div v-else>
            <h2 class="text-muted">:(</h2>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "DinnerItem",

    props: {
        homeFeed: String,
        days: Array,
        day: Object
    },
    methods: {
        toggleMeal() {
            this.day.dinner.complete = !this.day.dinner.complete;

            axios
                .post(this.homeFeed + '/dinner/' + this.day.dinner.uid, {
                    'complete': this.day.dinner.complete
                })

        },
    }
}
</script>

<style scoped>

.dinner-item {
    min-height: 150px;
    position: relative;
    width: 100%;
}

.dinner-item h2 {
    margin: 0;
    width: 100%;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.complete h2 {
    filter: blur(3px);
}
</style>
