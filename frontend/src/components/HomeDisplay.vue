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
                </g>
            </svg>
        </div>
        <div v-else>
            <header>
                <div class="previous-week" @click="previousWeek">
                    <svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 32 32" x="0px" y="0px"><title>Artboard 22</title>
                        <polygon points="30 16 21.906 30.018 14.542 30.018 22.626 16 14.542 1.982 21.906 1.982 30 16"/>
                        <polygon points="17.458 16 9.364 30.018 2 30.018 10.084 16 2 1.982 9.364 1.982 17.458 16"/>
                        <text x="0" y="47" fill="#000000" font-size="5px" font-weight="bold" font-family="'Helvetica Neue', Helvetica, Arial-Unicode, Arial, Sans-serif">Created by icon trip</text>
                        <text x="0" y="52" fill="#000000" font-size="5px" font-weight="bold" font-family="'Helvetica Neue', Helvetica, Arial-Unicode, Arial, Sans-serif">from the Noun Project</text>
                    </svg>
                </div>
                <date-time
                    :current-temp="data.currentTemp"
                    @currentWeek="currentWeek">
                </date-time>
                <div class="next-week" @click="nextWeek">
                    <svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 32 32" x="0px" y="0px"><title>Artboard 22</title>
                        <polygon points="30 16 21.906 30.018 14.542 30.018 22.626 16 14.542 1.982 21.906 1.982 30 16"/>
                        <polygon points="17.458 16 9.364 30.018 2 30.018 10.084 16 2 1.982 9.364 1.982 17.458 16"/>
                        <text x="0" y="47" fill="#000000" font-size="5px" font-weight="bold" font-family="'Helvetica Neue', Helvetica, Arial-Unicode, Arial, Sans-serif">Created by icon trip</text>
                        <text x="0" y="52" fill="#000000" font-size="5px" font-weight="bold" font-family="'Helvetica Neue', Helvetica, Arial-Unicode, Arial, Sans-serif">from the Noun Project</text>
                    </svg>
                </div>
            </header>
            <div class="week" v-if="data.days">
                <div class="day" v-for="(day) in data.days" :key="day.date" :class="[(isToday(day) ? 'today' : '')]">
                    {{ day.date_display }}
                    <dinner-item :days="data.days" :day="day" :home-feed="props.homeFeed"></dinner-item>
                    <div class="weather-day">
                        <weather-day v-if="day.weather" :icon="day.weather.icon_alt"
                                     :date="createDate(day.weather.startTime)"
                                     :high="day.weather.high"
                                     :low="day.weather.low"
                                     :description="day.weather.shortForecast"></weather-day>
                    </div>
                    <solar-daily :solar="day.solar" :solar-max="maxSolarValue" v-if="day.solar && day.solar.value">
                    </solar-daily>
                </div>
            </div>
            <footer>
                <div class="solar-benefits">
                    <div v-if="data.solarBenefits.benefits">
                        {{ Math.round(data.solarThisMonth / 1000).toLocaleString("en-US") }}kWh in {{ currentMonth }} •
                        {{ Math.round(data.solarBenefits.benefits.treesPlanted) }} Trees Saved •
                        {{ Math.round(data.solarBenefits.benefits.gasEmissionSaved.co2) }} {{
                            data.solarBenefits.benefits.gasEmissionSaved.units
                        }}
                        Reduced CO<sub>2</sub> Emissions
                    </div>
                </div>
                <div>
                    <ul class="buttons">
                        <li @click="statsVisible = true">Stats</li>
                        <li @click="refresh()">
                            Updated {{ updatedTimeAgo }}
                        </li>
                    </ul>
                </div>
            </footer>
        </div>
    </div>
    <div v-if="statsVisible">
        <stats-modal :home-feed="props.homeFeed" @closeModal="statsVisible = false"></stats-modal>
    </div>
</template>

<script setup>
import {computed, onMounted, reactive, ref} from 'vue';
import axios from 'axios';
import moment from 'moment-timezone';
import WeatherDay from "@/components/WeatherDay.vue";
import DateTime from "@/components/DateTime.vue";
import DinnerItem from "@/components/DinnerItem.vue";
import SolarDaily from "@/components/SolarDaily.vue";
import StatsModal from "@/components/StatsModal.vue";

const props = defineProps({
    homeFeed: String
});

const data = reactive({
    updated: null,
    currentTemp: null,
    days: [],
    solarBenefits: {},
});

const statsVisible = ref(false);
const fetching = ref(true);
const dataRefresh = 30000;
const secondsUntilRefresh = ref(0);
const startDate = ref(moment.tz(moment(), "America/Denver").startOf('week'));
const updatedTimeAgo = ref(null);

const currentMonth = computed(() => moment().format('MMMM'));
const maxSolarValue = computed(() => data.solar_daily_max);

const updateUpdatedTime = () => {
    updatedTimeAgo.value = timeAgo(data.updated);
};

const previousWeek = () => {
    fetching.value = true;
    startDate.value = startDate.value.subtract(1, 'week');
    fetch();
};

const nextWeek = () => {
    fetching.value = true;
    startDate.value = startDate.value.add(1, 'week');
    fetch();
};

const currentWeek = () => {
    fetching.value = true;
    startDate.value = moment.tz(moment(), "America/Denver").startOf('week').add(-1, 'day');
    fetch();
};

const createDate = (dateString) => new Date(dateString);

const scheduleFetch = (timeout) => {
    setInterval(() => fetch(), timeout);
    const reloadTimeout = 1000 * 60 * 60 * 24;
    setInterval(() => reload(), reloadTimeout);
};

const refresh = () => {
    fetching.value = true;
    const forceRefresh = 1;
    fetch(forceRefresh);
    setTimeout(() => {
        location.href = window.location.href;
    }, 1000);
};

const reload = () => {
    location.reload();
};

const fetch = (forceRefresh = 0) => {
    secondsUntilRefresh.value = dataRefresh;
    axios
        .get(`${props.homeFeed}/home?start_date=${startDate.value.format('MMMM Do YYYY, h:mm:ss a')}&force_refresh=${forceRefresh}`)
        .then(response => updateData(response));
};

const updateData = (response) => {
    data.currentTemp = response.data.current_weather?.current_temp;
    data.days = response.data.days;
    data.updated = moment(response.data.updated);
    data.solarThisMonth = response.data.solar_this_month;
    data.solarBenefits = response.data.solar_benefits;
    fetching.value = false;
};

const isToday = (day) => {
    const date = moment.tz(moment(day.date), "America/Denver");
    const today = moment.tz(moment(), "America/Denver");
    return date.isSame(today, "day");
};

const timeAgo = (time) => {
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

    const secondsElapsed = moment().diff(time, 'seconds');
    const dayStart = moment("2018-01-01").startOf('day').seconds(secondsElapsed);

    if (secondsElapsed > 300) {
        return moment(time).fromNow(true);
    } else if (secondsElapsed < 60) {
        return dayStart.format('s') + 's ago';
    } else {
        return dayStart.format('m:ss') + 'm ago';
    }
};

onMounted(() => {
    fetch();
    scheduleFetch(dataRefresh);
    setInterval(() => updateUpdatedTime(), 1000);
});
</script>

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
.next-week svg,
.previous-week svg {
    fill: #971c1e;
}

.next-week,
.previous-week {
    margin-top: 10px;
    font-size: 70px;
    text-align: center;
    color: #971c1e;
}
.previous-week svg {
    transform: rotate(180deg);
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