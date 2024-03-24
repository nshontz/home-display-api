<template>
    <div class="home-display darkmode">
        <div class="fetching" v-if="fetching">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin:auto;background:transparent;display:block;" width="200px" height="200px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                <g transform="translate(50,50)">
                    <circle cx="0" cy="0" r="8.333333333333334" fill="none" stroke="#e15b64" stroke-width="4" stroke-dasharray="26.179938779914945 26.179938779914945">
                        <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" times="0;1" dur="1s" calcMode="spline" keySplines="0.2 0 0.8 1" begin="0" repeatCount="indefinite"></animateTransform>
                    </circle>
                    <circle cx="0" cy="0" r="16.666666666666668" fill="none" stroke="#f47e60" stroke-width="4" stroke-dasharray="52.35987755982989 52.35987755982989">
                        <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" times="0;1" dur="1s" calcMode="spline" keySplines="0.2 0 0.8 1" begin="-0.2" repeatCount="indefinite"></animateTransform>
                    </circle>
                    <circle cx="0" cy="0" r="25" fill="none" stroke="#f8b26a" stroke-width="4" stroke-dasharray="78.53981633974483 78.53981633974483">
                        <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" times="0;1" dur="1s" calcMode="spline" keySplines="0.2 0 0.8 1" begin="-0.4" repeatCount="indefinite"></animateTransform>
                    </circle>
                    <circle cx="0" cy="0" r="33.333333333333336" fill="none" stroke="#abbd81" stroke-width="4" stroke-dasharray="104.71975511965978 104.71975511965978">
                        <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" times="0;1" dur="1s" calcMode="spline" keySplines="0.2 0 0.8 1" begin="-0.6" repeatCount="indefinite"></animateTransform>
                    </circle>
                    <circle cx="0" cy="0" r="41.666666666666664" fill="none" stroke="#849b87" stroke-width="4" stroke-dasharray="130.89969389957471 130.89969389957471">
                        <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" times="0;1" dur="1s" calcMode="spline" keySplines="0.2 0 0.8 1" begin="-0.8" repeatCount="indefinite"></animateTransform>
                    </circle>
                </g>
            </svg>
        </div>
        <div v-else>
            <header>
                <div class="previous-week" @click="this.previousWeek">
                    <font-awesome-icon :icon="['fas', 'angles-left']"/>
                </div>
                <date-time
                    :current-temp="this.data.currentTemp"
                    @currentWeek="this.currentWeek">
                </date-time>
                <div class="next-week" @click="this.nextWeek">
                    <font-awesome-icon :icon="['fas', 'angles-right']"/>
                </div>
            </header>
            <div class="week" v-if="this.data.days">
                <div class="day" v-for="(day) in this.data.days" :key="day.date" :class="[(isToday(day) ? 'today' : '')]">
                    {{ day.date_display }}
                    <div class="dinner-item">
                        <dinner-item :days="this.data.days" :day="day" :home-feed="homeFeed"></dinner-item>
                    </div>
                    <div class="weather-day">
                        <weather-day v-if="day.weather" :icon="day.weather.icon_alt"
                                     :date="this.createDate(day.weather.startTime)"
                                     :high="day.weather.high"
                                     :low="day.weather.low"
                                     :description="day.weather.shortForecast"></weather-day>
                    </div>
                    <solar-daily :solar="day.solar" :solar-max="this.maxSolarValue" v-if="day.solar && day.solar.value">
                    </solar-daily>
                </div>
            </div>
            <footer>
                <div class="solar-benefits">
                    <div v-if="this.data.solarBenefits.benefits">
                        {{ Math.round(this.data.solarThisMonth / 1000).toLocaleString("en-US") }}kWh in {{ this.currentMonth }} •
                        {{ Math.round(this.data.solarBenefits.benefits.treesPlanted) }} Trees Saved •
                        {{ Math.round(this.data.solarBenefits.benefits.gasEmissionSaved.co2) }} {{
                            this.data.solarBenefits.benefits.gasEmissionSaved.units
                        }}
                        Reduced CO<sub>2</sub> Emissions
                    </div>
                </div>
                <div>
                    <ul class="buttons">
                        <li @click="statsVisible = true">Stats</li>
                        <li @click="refresh()">
                            Updated {{ this.updatedTimeAgo }}
                        </li>
                    </ul>
                </div>
            </footer>
        </div>
    </div>
    <div v-if="statsVisible">
        <stats-modal :home-feed="homeFeed" @closeModal="statsVisible = false"></stats-modal>
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
import StatsModal from "@/components/StatsModal.vue";

export default {
    components: {StatsModal, SolarDaily, FontAwesomeIcon, DinnerItem, DateTime, WeatherDay},
    name: 'HomeDisplay',
    props: {
        homeFeed: String
    },
    data() {
        return {
            data: {
                updated: null,
                currentTemp: null,
                days: [],
                solarBenefits: {},
            },
            statsVisible: false,
            fetching: true,
            dataRefresh: 30000,
            secondsUntilRefresh: 0,
            startDate: null,
            updatedTimeAgo: null,
        }
    },
    mounted() {
        this.startDate = moment.tz(moment(), "America/Denver").startOf('week');
        this.fetch();
        this.scheduleFetch(this.dataRefresh);
        setInterval(() => this.updateUpdatedTime(), 1000)

    },
    methods: {
        updateUpdatedTime() {
            this.updatedTimeAgo = this.timeAgo(this.data.updated);
        },
        previousWeek() {
            this.fetching = true;
            this.startDate = this.startDate.subtract(1, 'week');
            this.fetch();
        },
        nextWeek() {
            this.fetching = true;
            this.startDate = this.startDate.add(1, 'week');
            this.fetch();
        },
        currentWeek() {
            this.fetching = true;
            this.startDate = moment.tz(moment(), "America/Denver").startOf('week').add(-1, 'day');
            this.fetch();
        },
        createDate(dateString) {
            return new Date(dateString);
        },
        scheduleFetch(timeout) {
            setInterval(() => this.fetch(), timeout);
            let reloadTimeout = 1000 * 60 * 60 * 24;
            setInterval(() => this.reload(), reloadTimeout);
        },
        refresh() {
            this.fetching = true;
            let forceRefresh = 1;
            this.fetch(forceRefresh);
            setTimeout(function () {
                location.href = window.location.href
            }, 1000);
        },
        reload() {
            location.reload();
        },
        fetch(forceRefresh = 0) {
            this.secondsUntilRefresh = this.dataRefresh;
            axios
                .get(this.homeFeed + '/home?start_date=' + this.startDate.format('MMMM Do YYYY, h:mm:ss a') + '&force_refresh=' + forceRefresh)
                .then(response => (this.updateData(response)))
        },
        updateData(response) {
            this.data.currentTemp = response.data.current_weather?.current_temp;
            this.data.days = response.data.days;
            this.data.updated = moment(response.data.updated);
            this.data.solarThisMonth = response.data.solar_this_month;
            this.data.solarBenefits = response.data.solar_benefits;
            this.fetching = false;
        },
        isToday(day) {

            let date = moment.tz(moment(day.date), "America/Denver");
            let today = moment.tz(moment(), "America/Denver");

            let isToday = date.isSame(today, "day");


            return isToday;
        },
        timeAgo(time) {
            moment.updateLocale('en', {
                relativeTime: {
                    future: "in %s",
                    past: "%s ago",
                    s: number => number + "s ago",
                    ss: '%ds ago',
                    m: "1m ago",
                    mm: "%dm ago",
                    h: "1h ago",
                    hh: "%dh ago",
                    d: "1d ago",
                    dd: "%dd ago",
                    M: "a month ago",
                    MM: "%d months ago",
                    y: "a year ago",
                    yy: "%d years ago"
                }
            });

            let secondsElapsed = moment().diff(time, 'seconds');
            let dayStart = moment("2018-01-01").startOf('day').seconds(secondsElapsed);

            if (secondsElapsed > 300) {
                return moment(time).fromNow(true);
            } else if (secondsElapsed < 60) {
                return dayStart.format('s') + 's ago';
            } else {
                return dayStart.format('m:ss') + 'm ago';
            }
        },
    },
    computed: {
        currentMonth() {
            return moment().format('MMMM');

        },
        maxSolarValue() {
            return this.data.solar_daily_max;
        },
    }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.home-display.darkmode {
    background-color: #12151c;
    color: #eee;
    @media screen and (max-width: 1000px) {
        height: auto;
        max-height: none;
    }
}

.fetching {
    margin-top: 240px;
}

.weather-day {
    height: 100px;
}

.dinner-item {
    min-height: 170px;
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
    @media screen and (max-width: 1000px) {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 350px;
    }
}

.day {
    text-align: center;
    border-radius: 15px;
    background-color: rgb(26, 29, 45);
    margin: 10px;
    padding: 10px;
    @media screen and (max-width: 1000px) {
        width: 80%;
        min-width: 230px;
        margin: 0 auto 20px;
    }
}

.next-week,
.previous-week {
    margin-top: 10px;
    font-size: 70px;
    text-align: center;
    color: #971c1e;
}

.buttons li {
    display: inline-block;
    margin-right: 20px;
}

.buttons {
    float: right;
    margin-top: 0;
    list-style: none;
    margin-right: 20px;
}

.today {
    background-color: rgb(37, 51, 80);
}

.solar-benefits {
    margin-left: 10px;
    text-align: left;
    font-size: 1.2rem
}
</style>
