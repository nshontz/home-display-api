<script setup>

import axios from "axios";
import {ref, defineProps, defineEmits} from "vue";
import {Pie} from 'vue-chartjs'
import {Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale,Colors, ArcElement} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement,Colors)
ChartJS.defaults.color = '#fff';

let fetching = ref(false)
let dinnerFrequency = ref([])
let proteinFrequency = ref([])
let vegetarianFrequency = ref({})
const props = defineProps(['homeFeed'])
const emit = defineEmits(['closeModal'])


fetch()

function updateData(response) {
    dinnerFrequency.value = response.data.dinner_frequency
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
        scales: {

        },
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
    console.log(colors);

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
                <ul>
                </ul>
                <table>
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
            <div class="popular-protein" v-if="proteinFrequency.length > 0">
                <h2>The Breakdown</h2>
                <div class="h-50">
                    <Pie
                        id="protein-chart"
                        :options="getChartOptions()"
                        :data="getChartData()"
                    />
                </div>
            </div>
            <div class="popular-" v-if="proteinFrequency.length > 0">

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
table {

    font-size: 1.2rem;
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
    grid-template-columns: 1fr 1fr 1fr;
}

.close-button {
    float: right;
    padding: 10px 15px;
    color: #ededed;
}
</style>
