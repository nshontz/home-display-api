<template>
    <div class="solar-bar" :style="{ 'width': this.intensityValue(solar.value) + '%' }">
    </div>
</template>

<script>
export default {
    name: "SolarDaily",
    props: {
        solar: Object,
        solarMax: {
            type: Number,
            default: 45000
        },
    },
    methods: {
        intensityValue(value) {
            let intensity = (value / this.solarMax) * 100;
            return 100 - intensity;
        },
        calculateLabelPadding(value) {
            let padding = this.intensityValue(value) + 3;

            if (padding > 60) {
                padding = padding - 22;
            }

            return padding;

        }
    }
}
</script>

<style scoped>
.solar-bar {
    position: relative;
    border-radius: 20px;
    height: 30px;
    width: 100%;
    background-image: linear-gradient(to right, #1a1d2d, #FF272A);
}
.today .solar-bar {

    background-image: linear-gradient(to right, #253350, #FF272A);
}

.label {
    top: 0;
    width: 100%;
    position: absolute;
    padding: 0px 0px;
    text-align: right;
    color: white;
    text-shadow: 0px 0px 2px #12151c;
    box-sizing: border-box
}

.gradient {
    display: none;
    top: 0;
    right: 0;
    position: absolute;
    height: 30px;
    background-color: #12151c;
    border-radius: 20px;
}
.today .gradient {
    background-color: #263350;
}
</style>
