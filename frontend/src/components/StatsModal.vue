<script setup>

import axios from "axios";
import {ref, defineProps, defineEmits} from "vue";
import {Pie, Bar} from 'vue-chartjs'
import {Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors, ArcElement} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, Colors)
ChartJS.defaults.color = '#fff';

let fetching = ref(false)
let dinnerFrequency = ref([])
let dinnerRecommendations = ref([])
let proteinFrequency = ref([])
let energyData = ref([])
let vegetarianFrequency = ref({})
const props = defineProps(['homeFeed'])
const emit = defineEmits(['closeModal'])


fetch()

function updateData(response) {
    energyData.value = response.data.energy_report
    dinnerFrequency.value = response.data.dinner_frequency
    dinnerRecommendations.value = response.data.dinner_recommendations
    proteinFrequency.value = response.data.protein_frequency
    vegetarianFrequency.value = response.data.vegetarian_frequency
    fetching.value = false;
}

function closeModal() {
    emit('closeModal')
}

function getChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        scales: {},
        legend: {
            labels: {
                fontColor: '#fffff'
            }
        },
        plugins: {
            colors: {
                enabled: true
            },
            legend: {
                display: true,
                position: 'right',
            }
        }
    }
}

function getChartData() {
    let labels = [];
    let values = [];
    let colors = [];

    proteinFrequency.value.map((protein) => {
        colors.push(protein.color ?? null)
        labels.push(protein.name)
        values.push(protein.freq)
    })

    return {
        labels: labels,
        datasets: [
            {
                data: values,
                backgroundColor: colors,
            }
        ]
    }
}

function getSolarChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        scales: {},
        legend: {
            labels: {
                fontColor: '#fffff'
            }
        },
        plugins: {
            colors: {
                enabled: true
            },
            legend: {
                display: false,
                position: 'right',
            }
        }
    }
}

function getSolarChartData() {
    let labels = [];
    let solarValues = [];
    let consumptionValues = [];

    energyData.value.map((month) => {
        labels.push(month.month_label)
        solarValues.push(month.generated_value)
        consumptionValues.push(month.consumption_value)
    })

    return {
        labels: labels,
        datasets: [
            {
                data: solarValues,
                backgroundColor: '#050',
            }
        ]
    }
}

function fetch() {
    fetching.value = true;
    axios
        .get(props.homeFeed + '/dinner/stats')
        .then(response => (updateData(response)))
}
</script>

<template>
    <div class="stats-modal">
        <div class="close-button" @click="closeModal">X</div>
        <div v-if="fetching">
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
        <div v-else class="stats-content">
            <div class="popular-dinners">
                <h2>Popular Dinners</h2>
                <table class="">
                    <thead>
                    <tr>
                        <th class="text-left">Dinner</th>
                        <th>Frequency</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="dinner in dinnerFrequency" :key="dinner.title">
                        <td>{{ dinner.title }}</td>
                        <td class="text-right">{{ dinner.freq }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="recommended-dinners">
                <h2>Dinner Suggestions</h2>
                <ul>
                    <li v-for="dinner in dinnerRecommendations" :key="dinner.title">
                        {{ dinner.title }}
                    </li>
                </ul>
            </div>
            <div class="popular-protein px-5" v-if="proteinFrequency.length > 0">
                <h2>Protein Breakdown</h2>
                <div class="h-50">
                    <Pie
                        id="protein-chart"
                        :options="getChartOptions()"
                        :data="getChartData()"
                    />
                </div>
            </div>
            <div class="solar-stats" v-if="proteinFrequency.length > 0">
                <h2>Solar Generation</h2>
                <div class="h-50">
                    <Bar
                        id="solar-chart"
                        :options="getSolarChartOptions()"
                        :data="getSolarChartData()"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stats-modal {
    position: absolute;
    top: 3%;
    left: 1%;
    width: 98%;
    height: 94%;
    background-color: #2d3748;
}

.solar-stats canvas {
    height: 250px !important;
}

.px-5 {
    padding: 0px 20px;
}

table {
    font-size: 1rem;
}

.text-right {
    text-align: right;
}

.text-left {
    text-align: left;
}

.stats-content {
    padding: 0px 20px;
    color: #ededed;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
}

.close-button {
    float: right;
    padding: 10px 15px;
    color: #ededed;
}
</style>
