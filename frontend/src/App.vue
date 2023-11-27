<template>
    <HomeDisplay home-feed="/api"/>
</template>

<script>
import HomeDisplay from './components/HomeDisplay.vue'

export default {
    name: 'App',
    components: {
        HomeDisplay
    }
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Abel&family=IBM+Plex+Sans:wght@200&display=swap');

#app {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

body {
    font-family: 'IBM Plex Sans', sans-serif;
    margin: 0;
    background-color: #12151c;
    cursor: none;
    overflow: hidden;

    @media screen and (max-width: 1000px) {
        overflow: auto;
    }
}
</style>
