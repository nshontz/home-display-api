<template>
    <div class="home-display">
        <header>
            <div class="previous-week" @click="this.previousWeek">
                <font-awesome-icon :icon="['fas', 'angles-left']"/>
            </div>
            <date-time
                @currentWeek="this.currentWeek">
            </date-time>
            <div class="next-week" @click="this.nextWeek">
                <font-awesome-icon :icon="['fas', 'angles-right']"/>
            </div>
        </header>
        <div class="week" v-if="this.data.days">
            <div class="day" v-for="(day) in this.data.days" :key="day.date" :class="[( isToday(day) ? 'today' : '')]">
                {{ day.date_display }}
                <div class="dinner-item">
                    <dinner-item
                        :days="this.data.days"
                        :day="day"
                        :home-feed="homeFeed"
                    ></dinner-item>
                </div>
                <div class="weather-day">
                    <weather-day
                        v-if="day.weather"
                        :icon="day.weather.icon_alt"
                        :date="this.createDate(day.weather.startTime)"
                        :high="day.weather.high"
                        :low="day.weather.low"
                        :description="day.weather.shortForecast"
                    ></weather-day>
                </div>
                <div class="solar">
                    <solar-daily
                        :solar="day.solar"
                        :solar-max="this.maxSolarValue"
                        v-if="day.solar && day.solar.value">
                    </solar-daily>
                </div>
            </div>
        </div>
        <footer>
            <div class="solar-benefits">
                <div v-if="this.data.solar_benefits.benefits">
                    {{ Math.round(this.data.solar_benefits.benefits.treesPlanted) }} Trees Saved •
                    {{ this.data.solar_benefits.benefits.gasEmissionSaved.co2 }} {{ this.data.solar_benefits.benefits.gasEmissionSaved.units }}
                    Reduced CO<sub>2</sub> Emissions
                </div>
            </div>
            <div class="refresh" @click="refresh()">
                refresh
            </div>
        </footer>
    </div>
</template>

<script>
import axios from 'axios'
import 'vue-cal/dist/vuecal.css'
import WeatherDay from "@/components/WeatherDay.vue";
import DateTime from "@/components/DateTime.vue";
import DinnerItem from "@/components/DinnerItem.vue";
import moment from 'moment-timezone';
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import SolarDaily from "@/components/SolarDaily.vue";

export default {
    components: {SolarDaily, FontAwesomeIcon, DinnerItem, DateTime, WeatherDay},
    name: 'Home',
    props: {
        homeFeed: String
    },
    data() {
        return {
            data: {
                currentTemp: null,
                days: [],
                solar_benefits: {},
            },
            dataRefresh: 5,
            secondsUntilRefresh: 0,
            startDate: null,
        }
    },
    mounted() {
        this.startDate = moment.tz(moment(), "America/Denver").startOf('week').add(-1, 'day');
        this.fetch();
        this.scheduleFetch(this.dataRefresh);
    },
    methods: {
        previousWeek() {
            this.startDate = this.startDate.subtract(1, 'week');
            this.fetch();
        },
        nextWeek() {
            this.startDate = this.startDate.add(1, 'week');
            this.fetch();
        },
        currentWeek() {
            this.startDate = moment.tz(moment(), "America/Denver");
            this.fetch();
        },
        createDate(dateString) {
            return new Date(dateString);
        },
        scheduleFetch(timeout) {
            setTimeout(() => this.fetch(), timeout);
        },
        refresh() {
            let forceRefresh = 1;
            this.fetch(forceRefresh);
            setTimeout(function () {
                location.href = window.location.href
            }, 1000);
        },
        fetch(forceRefresh = 0) {
            this.secondsUntilRefresh = this.dataRefresh;
            axios
                .get(this.homeFeed + '/home?start_date=' + this.startDate.format('MMMM Do YYYY, h:mm:ss a') + '&force_refresh=' + forceRefresh)
                .then(response => (this.updateData(response)))
        },
        updateData(response) {
            this.data.currentTemp = response.data.current_weather.current_temp;
            this.data.days = response.data.days;
            this.data.solar_benefits = response.data.solar_benefits;
            console.log(this.data.days);
        },
        isToday(day) {

            let date = moment.tz(moment(day.date), "America/Denver").add(1,'day');
            let today = moment.tz(moment(), "America/Denver");

            let isToday = date.isSame(today, "day");

            if (isToday) {
                console.log('tody',
                    [
                        day,
                        date.format('MMMM Do YYYY, h:mm:ss a')
                    ]
                );
            }

            return isToday;
        }
    },
    computed: {
        maxSolarValue() {
            let max = 0;
            this.data.days.forEach(function (day) {
                if (day.solar.value > max) {
                    max = day.solar.value
                }
            })
            return max
        },
    }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.weather-day {
    height: 170px;
}

.dinner-item {
    min-height: 120px;
    position: relative;
    width: 100%;
}

.home-display {
    max-height: 545px;
    overflow: hidden;
}

header {
    display: grid;
    grid-template-columns: 100px 1fr 100px;
}

footer {
    margin-top: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
}


.week {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
    min-height: 350px;
}

.day {
    text-align: center;
    padding: 10px;
}

.next-week,
.previous-week {
    margin-top: 25px;
    font-size: 70px;
    text-align: center;
    color: #a0aec0;
}

.refresh {
    text-align: right;
    margin-right: 100px;
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
