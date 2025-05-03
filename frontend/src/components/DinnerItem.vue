<template>
    <div class="dinner-item" :class="[
        (dinner && dinner.complete ? 'complete' : ''),
        (dinner && dinner.recipe_url ? 'has-recipe' : '')
        ]">
        <template v-if="dinner">
            <h2 @click="toggleMeal()" class="dinner-name">{{ dinner.title }}</h2>
            <div v-if="dinner.recipe_url" class="recipe-link">
                <a :href="dinner.recipe_url" target="_blank">

                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 30" version="1.1" x="0px" y="0px"><title>Icon/Stroke/Share</title>
                        <desc>Created with Sketch.</desc>
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <rect x="0" y="0" width="24" height="24"/>
                            <rect x="0" y="0" width="24" height="24"/>
                            <path d="M19.5,4.5 L9.5,14.5" stroke="#FFFFFF"/>
                            <polyline stroke="#FFFFFF" points="13 4.5 19.5072883 4.5 19.5072883 10.9377311"/>
                            <polyline stroke="#FFFFFF" points="10 6.5 4.5 6.5 4.5 19.5 17.5036627 19.5 17.5 14"/>
                        </g>
                        <text x="0" y="39" fill="#000000" font-size="5px" font-weight="bold" font-family="'Helvetica Neue', Helvetica, Arial-Unicode, Arial, Sans-serif">Created by Jake Park</text>
                        <text x="0" y="44" fill="#000000" font-size="5px" font-weight="bold" font-family="'Helvetica Neue', Helvetica, Arial-Unicode, Arial, Sans-serif">from the Noun Project</text>
                    </svg>

                </a>
            </div>
        </template>
        <div v-else>
            <h2 class="text-muted">--</h2>
        </div>
    </div>
</template>
<script setup>
import {ref} from "vue";
import axios from "axios";

const props = defineProps({
    homeFeed: String,
    days: Array,
    day: Object
});

const dinner = ref(props.day.dinner);

const toggleMeal = () => {
    if (dinner.value) {
        dinner.value.complete = !dinner.value.complete;

        axios.post(`${props.homeFeed}/dinner/${dinner.value.uid}`, {
            complete: dinner.value.complete
        });
    }
};
</script>

<style scoped>

.dinner-item {
    min-height: 170px;
    position: relative;
    width: 100%;
}

h2 {
    margin: 0;
    width: 100%;
    font-size: 2rem;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.complete h2 {
    filter: blur(3px);
    color: #7c7f8c;
}

.dinner-name {

}

.recipe-link a svg {
    height: 30px;
    width: 30px;
}

.complete .recipe-link a {
    filter: blur(3px);
}
.recipe-link a {
    color: #7c7f8c;
    text-decoration: none;
}

.recipe-link {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 0.8rem;
    text-decoration: none;
}
</style>
