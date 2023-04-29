<template>
    <div class="home-display">
        <date></date>
        <div class="week" v-if="this.days">
            <div class="day" v-for="(day) in this.days" :key="day.date" :class="[( isToday(day.date) ? 'today' : '')]">
                {{ day.date_display }}
                <dinner-item
                    :days="this.days"
                    :day="day"
                    :home-feed="homeFeed"
                ></dinner-item>
                <weather-day
                    v-if="day.weather"
                    :icon="day.weather.icon_alt"
                    :date="this.createDate(day.weather.startTime)"
                    :high="day.weather.temperature"
                    :low="day.weather.temperature"
                    :description="day.weather.shortForecast"
                ></weather-day>
                <div class="solar" v-if="day.solar && day.solar.value">
                    {{ Math.round(day.solar.value / 10) / 100 }} K{{ day.solar.unit }}
                </div>
            </div>
        </div>
        <div class="footer">
            <div class="solar-benefits">
                <div v-if="this.solarData">
                    {{ Math.round(this.solarData.benefits.treesPlanted) }} Trees Saved •
                    {{ this.solarData.benefits.gasEmissionSaved.co2 }} {{ this.solarData.benefits.gasEmissionSaved.units }}
                    Reduced CO<sub>2</sub> Emissions
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import 'vue-cal/dist/vuecal.css'
import WeatherDay from "@/components/WeatherDay.vue";
import DateTime from "@/components/DateTime.vue";
import DinnerItem from "@/components/DinnerItem.vue";

export default {
    components: {DinnerItem, DateTime, WeatherDay},
    name: 'Home',
    props: {
        homeFeed: String
    },
    data() {
        return {
            data: [],
            dataRefresh: 5,
            secondsUntilRefresh: 0,
        }
    },
    methods: {
        createDate(dateString) {
            return new Date(dateString);
        },
        scheduleFetch(timeout) {
            setTimeout(() => this.fetch(), timeout);
        },
        fetch() {
            this.secondsUntilRefresh = this.dataRefresh;
            axios
                .get(this.homeFeed + '/home')
                .then(response => (this.data = response.data))
        },
        isToday(dateString) {
            const date = new Date(new Date(dateString).toLocaleString('en', {timeZone: 'America/Denver'}))
            const today = new Date(new Date().toLocaleString('en', {timeZone: 'America/Denver'}))

            return date.getDate() == today.getDate() - 1 &&
                date.getMonth() == today.getMonth() &&
                date.getFullYear() == today.getFullYear()
        }
    },
    mounted() {
        this.fetch();
        this.scheduleFetch(this.dataRefresh);
    },
    computed: {
        days() {
            return this.data.days;
        },
        solarData() {
            return this.data.solar;
        }
    }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.footer {
    display: grid;
    grid-template-columns: 1fr 1fr;
}


.week {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
}

.day {
    text-align: center;
    padding-top: 40px;
}


.today {
    background-color: #ADD8E6;
}

.solar-benefits {
    margin-left: 100px;
    text-align: left;
    font-size: 1.4rem
}
</style>
