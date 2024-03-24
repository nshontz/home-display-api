<template>
    <div class="solar">

        <div class="solar-bar" :style="{ 'width': this.intensityValue(solar.value) + '%' }">
        </div>
        <div class="solar-value" :class="{'text-left': this.intensityValue(solar.value) > 40}">
            {{solarLabel}}
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
    computed: {
      solarLabel() {
          let label = this.solar.value+this.solar.unit;

          if(this.solar.value > 1000){
              label = (this.solar.value/1000).toFixed(1)+'k'+this.solar.unit
          }

          return label;
      }
    },
    methods: {
        intensityValue(value) {
            let intensity = (value / this.solarMax) * 100;
            return  intensity;
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
.solar {
    position: relative;
}
.solar-value {
    position: absolute;
    text-align: right;
    top: 4px;
    padding-right: 5px;
    width: 100%;
}

.text-left {
    text-align: left !important;
}
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
