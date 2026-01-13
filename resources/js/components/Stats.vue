<script setup>
import axios from "axios";
import { ref, onMounted } from "vue";
import { Pie, Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors, ArcElement } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, Colors)
ChartJS.defaults.color = '#fff';

let fetching = ref(false)
let dinnerFrequency = ref([])
let dinnerRecommendations = ref([])
let proteinFrequency = ref([])
let energyData = ref([])
let vegetarianFrequency = ref({})

function updateData(response) {
    energyData.value = response.data.energy_report
    dinnerFrequency.value = response.data.dinner_frequency
    dinnerRecommendations.value = response.data.dinner_recommendations
    proteinFrequency.value = response.data.protein_frequency
    vegetarianFrequency.value = response.data.vegetarian_frequency
    fetching.value = false;
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
        .get('/api/dinner/stats')
        .then(response => (updateData(response)))
}

onMounted(() => {
    fetch();
});
</script>

<template>
    <div class="stats-page page-container">
        <header>
            <h1>Statistics</h1>
            <router-link to="/" class="back-button">← Back to Home</router-link>
        </header>

        <div class="loading" v-if="fetching">
            <svg xmlns="http://www.w3.org/2000/svg" style="margin:auto;background:transparent;display:block;" width="200px" height="200px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
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
            <div class="popular-protein px-5" v-if="proteinFrequency.length > 0">
                <h2>Protein Breakdown</h2>
                <div class="chart-container">
                    <Pie
                        id="protein-chart"
                        :options="getChartOptions()"
                        :data="getChartData()"
                    />
                </div>
            </div>
            <div class="solar-stats" v-if="energyData.length > 0">
                <h2>Solar Generation</h2>
                <div class="chart-container">
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
.stats-content {
    padding: 20px;
    display: grid;
    grid-template-columns:  1fr 1fr;
    gap: 30px;
    background-color: #1a1d2d;
    border-radius: 12px;
}

@media screen and (max-width: 1000px) {
    .stats-content {
        grid-template-columns: 1fr;
    }
}

.popular-dinners,
.recommended-dinners,
.popular-protein,
.solar-stats {
    background-color: #252d3d;
    padding: 20px;
    border-radius: 8px;
}

h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 1.3rem;
    color: #eee;
    border-bottom: 2px solid #2d3548;
    padding-bottom: 10px;
}

table {
    width: 100%;
    font-size: 1rem;
    border-collapse: collapse;
}

table thead {
    background-color: #2d3548;
}

table th {
    padding: 10px;
    text-align: left;
    font-weight: 600;
}

table td {
    padding: 10px;
    border-bottom: 1px solid #2d3548;
    color: #ddd;
}

table tbody tr:hover {
    background-color: #2d3548;
}

.text-right {
    text-align: right;
}

.text-left {
    text-align: left;
}

.recommended-dinners ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recommended-dinners li {
    padding: 8px 0;
    border-bottom: 1px solid #2d3548;
    color: #ddd;
}

.recommended-dinners li:last-child {
    border-bottom: none;
}

.px-5 {
    padding: 0px 20px;
}

.chart-container {
    height: 300px;
    margin-top: 20px;
}

.solar-stats .chart-container {
    height: 250px;
}
</style>
