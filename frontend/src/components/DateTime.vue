<template>
    <div class="date-time">

        <div class="time">
            <div v-if="this.datetime && this.time">{{ this.time }}</div>
        </div>
        <div class="current">
            <div v-if="currentTemp">
                {{ Math.round(currentTemp) }}º
                <div class="d-inline small">Outside</div>
            </div>
        </div>
        <div class="date">
            <div v-if="this.datetime && this.date" @click="this.emitCurrentWeek">{{ this.date }}</div>
        </div>
    </div>
</template>

<script>
import moment from "moment";

export default {
    name: "DateTime",
    mounted() {
        setInterval(() => this.updateDatetime(), 1000)
    },
    props: {
        currentTemp: null
    },
    data() {
        return {
            datetime: null,
        }
    },
    methods: {
        emitCurrentWeek() {
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
.d-inline {
    display: inline-block;
}

.small {
    font-size: 1.5rem;
}

.date-time {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    height: 100px;
}

.time {
    font-size: 5.5rem;
}

.current {
    text-align: center;
    font-size: 3rem;
    padding-top: 3rem;
}

.date {
    text-align: right;
    margin-top: 30px;
    font-size: 3.5rem;
}
</style>
