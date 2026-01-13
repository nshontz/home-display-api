<template>
    <div class="dinner-list page-container">
        <header>
            <h1>Dinners</h1>
            <router-link to="/" class="back-button">← Back to Home</router-link>
        </header>

        <div class="loading" v-if="loading">
            <svg xmlns="http://www.w3.org/2000/svg" style="margin:auto;background:transparent;display:block;" width="100px" height="100px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                <circle cx="50" cy="50" r="32" stroke-width="8" stroke="#e15b64" stroke-dasharray="50.26548245743669 50.26548245743669" fill="none" stroke-linecap="round">
                    <animateTransform attributeName="transform" type="rotate" dur="1s" repeatCount="indefinite" keyTimes="0;1" values="0 50 50;360 50 50"></animateTransform>
                </circle>
            </svg>
        </div>

        <div v-else class="content-grid">
            <div class="table-container">
                <h2>All Dinners</h2>
                <table class="dinner-table">
                    <thead>
                        <tr>
                            <th @click="sortBy('title')" class="sortable">
                                Dinner Name
                                <span v-if="sortKey === 'title'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="sortBy('frequency')" class="sortable">
                                Frequency
                                <span v-if="sortKey === 'frequency'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="sortBy('last_eaten')" class="sortable">
                                Last Eaten
                                <span v-if="sortKey === 'last_eaten'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th>Recipe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="dinner in sortedDinners" :key="dinner.title">
                            <td class="dinner-name">{{ dinner.title }}</td>
                            <td class="frequency">{{ dinner.frequency }}</td>
                            <td class="last-eaten">{{ formatDate(dinner.last_eaten) }}</td>
                            <td class="recipe">
                                <a v-if="dinner.recipe_url" :href="dinner.recipe_url" target="_blank" class="recipe-link">
                                    🔗 View Recipe
                                </a>
                                <span v-else class="no-recipe">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="recommendations-container">
                <h2>Dinner Suggestions</h2>
                <p class="suggestions-description">Based on meals you haven't had recently and seasonal favorites</p>
                <ul class="recommendations-list">
                    <li v-for="recommendation in recommendations" :key="recommendation.title">
                        {{ recommendation.title }}
                        <span class="weight-badge" v-if="recommendation.weight > 1">★</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment-timezone';

const dinners = ref([]);
const recommendations = ref([]);
const loading = ref(true);
const sortKey = ref('frequency');
const sortOrder = ref('desc');

const fetchDinners = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/dinner/list');
        dinners.value = response.data.dinners;
        recommendations.value = response.data.recommendations;
    } catch (error) {
        console.error('Error fetching dinners:', error);
    } finally {
        loading.value = false;
    }
};

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = key === 'title' ? 'asc' : 'desc';
    }
};

const sortedDinners = computed(() => {
    const sorted = [...dinners.value].sort((a, b) => {
        let aVal = a[sortKey.value];
        let bVal = b[sortKey.value];

        // Handle null/undefined for last_eaten
        if (sortKey.value === 'last_eaten') {
            if (!aVal) return 1;
            if (!bVal) return -1;
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        }

        if (sortKey.value === 'title') {
            aVal = aVal?.toLowerCase() || '';
            bVal = bVal?.toLowerCase() || '';
        }

        if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return sorted;
});

const formatDate = (date) => {
    if (!date) return 'Never';
    return moment.tz(date, 'America/Denver').format('MMM D, YYYY');
};

onMounted(() => {
    fetchDinners();
});
</script>

<style scoped>
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

@media screen and (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

.table-container,
.recommendations-container {
    background-color: #1a1d2d;
    border-radius: 12px;
    padding: 20px;
}

.table-container h2,
.recommendations-container h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.3rem;
    color: #eee;
    border-bottom: 2px solid #2d3548;
    padding-bottom: 10px;
}

.table-container {
    overflow-x: auto;
}

.dinner-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1rem;
}

.dinner-table thead {
    background-color: #252d3d;
}

.dinner-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #eee;
    border-bottom: 2px solid #2d3548;
}

.dinner-table th.sortable {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}

.dinner-table th.sortable:hover {
    background-color: #2d3548;
}

.dinner-table th.sortable span {
    margin-left: 5px;
    font-size: 0.8rem;
}

.dinner-table tbody tr {
    border-bottom: 1px solid #2d3548;
    transition: background-color 0.2s;
}

.dinner-table tbody tr:hover {
    background-color: #252d3d;
}

.dinner-table td {
    padding: 15px;
    color: #ddd;
}

.dinner-name {
    font-weight: 500;
    color: #eee;
}

.frequency {
    font-weight: 600;
    color: #f8b26a;
}

.last-eaten {
    color: #aaa;
}

.recipe-link {
    color: #5ba3f5;
    text-decoration: none;
    transition: color 0.2s;
}

.recipe-link:hover {
    color: #7bb8ff;
    text-decoration: underline;
}

.no-recipe {
    color: #666;
}

.suggestions-description {
    color: #aaa;
    font-size: 0.9rem;
    margin: 0 0 15px 0;
    font-style: italic;
}

.recommendations-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recommendations-list li {
    padding: 12px 15px;
    margin-bottom: 8px;
    background-color: #252d3d;
    border-radius: 6px;
    color: #ddd;
    transition: background-color 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.recommendations-list li:hover {
    background-color: #2d3548;
}

.weight-badge {
    color: #f8b26a;
    font-size: 1.2rem;
    margin-left: 10px;
}

@media screen and (max-width: 768px) {
    .dinner-table {
        font-size: 0.9rem;
    }

    .dinner-table th,
    .dinner-table td {
        padding: 10px;
    }
}
</style>