<template>
    <div class="date-time">
        <div class="time" v-if="this.datetime && this.time">{{ this.time }}</div>
        <div class="date" v-if="this.datetime && this.date" @click="this.emitCurrentWeek">{{ this.date }}</div>
    </div>
</template>

<script>
import moment from "moment";

export default {
    name: "DateTime",
    mounted() {
        setInterval(() => this.updateDatetime(), 1000)
    },
    data() {
        return {
            datetime: null,
        }
    },
    methods: {
        emitCurrentWeek(){
            this.$emit('currentWeek')
        },
        updateDatetime() {
            this.datetime = moment();
        }
    },
    computed: {
        time() {
            return this.datetime.format('h:mm');
        },
        date() {
            return this.datetime.format('MMMM Do, YYYY');
        },
    }
}
</script>

<style scoped>

.date-time {
    display: grid;
    grid-template-columns: 1fr 1fr;
    height: 100px;
}

.time {
    font-size: 5.5rem;
}

.date {
    text-align: right;
    margin-top: 30px;
    font-size: 4rem;
}
</style>
