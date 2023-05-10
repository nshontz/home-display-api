<template>
    <div class="solar-bar">
        <div class="gradient" :style="{ 'width': this.intensityValue(solar.value) + '%' }"></div>
        <div class="label" :style="{ 'padding-right': this.calculateLabelPadding(solar.value) + '%' }">
            {{ Math.round(solar.value / 10) / 100 }}
        </div>
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
    height: 22px;
    width: 100%;
    background-image: linear-gradient(to right, #12151c, #FF272A);
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
    top: 0;
    right: 0;
    position: absolute;
    height: 22px;
    background-color: #12151c;
}
.today .gradient {
    background-color: #263350;
}
</style>
