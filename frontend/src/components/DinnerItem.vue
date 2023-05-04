<template>
    <div :class="[( dinner && dinner.complete?'complete' :'')]">
        <div v-if="dinner" @click="toggleMeal()">
            <h2>{{ dinner.title }}</h2>
        </div>
        <div v-else>
            <h2 class="text-muted">--</h2>
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
    data() {
        return {
            dinner: null
        }
    },
    mounted() {
        this.dinner = this.day.dinner;
    },
    methods: {
        toggleMeal() {
            this.dinner.complete = !this.dinner.complete;

            axios
                .post(this.homeFeed + '/dinner/' + this.dinner.uid, {
                    'complete': this.dinner.complete
                })

        },
    }
}
</script>

<style scoped>


h2 {
    margin: 0;
    width: 100%;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.complete h2 {
    filter: blur(3px);
    color: #7c7f8c;
}
</style>
