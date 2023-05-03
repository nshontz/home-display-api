<template>
    <div class="home-display">
        <header>
            <div class="previous-week" @click="this.previousWeek">
                <font-awesome-icon :icon="['fas', 'angles-left']" />
            </div>
            <date-time
                @currentWeek="this.currentWeek">
            </date-time>
            <div class="next-week" @click="this.nextWeek">
                <font-awesome-icon :icon="['fas', 'angles-right']" />
            </div>
        </header>
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
        <footer>
            <div class="solar-benefits">
                <div v-if="this.solarData.benefits">
                    {{ Math.round(this.solarData.benefits.treesPlanted) }} Trees Saved •
                    {{ this.solarData.benefits.gasEmissionSaved.co2 }} {{ this.solarData.benefits.gasEmissionSaved.units }}
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
import moment from "moment";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";

export default {
    components: {FontAwesomeIcon, DinnerItem, DateTime, WeatherDay},
    name: 'Home',
    props: {
        homeFeed: String
    },
    data() {
        return {
            data: {
                days: [],
                solar_benefits: {},
            },
            dataRefresh: 5,
            secondsUntilRefresh: 0,
            startDate: null,
        }
    },
    mounted() {
        this.startDate = moment().startOf('week').add(1,'day');
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
            this.startDate = moment();
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
            setTimeout(function (){
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
            this.data.days = response.data.days;
            this.data.solar_benefits = response.data.solar_benefits;
        },
        isToday(dateString) {
            return moment(dateString).isSame(moment(), "day");
        }
    },
    computed: {
        days() {
            return this.data.days;
        },
        solarData() {
            return this.data.solar_benefits;
        }
    }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

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
}

.day {
    text-align: center;
    padding: 10px ;
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
