<template>
    <div class="container" id="app">
        <div class="jumbotron">
            <h1 class="display-4">Спарсить данные</h1>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Вставьте<br>текст<br>сюда</span>
                </div>
                <textarea class="form-control" v-model="query"></textarea>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            @click="getResult"
                    >Получить данные
                    </button>
                </div>
            </div>
            <div class="form-group form-check">
                <input type="checkbox"
                       v-model="isPre"
                       class="form-check-input"
                       id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">Отображать сырой html</label>
            </div>

            <pre v-if="isPre"
                style="white-space: pre-wrap"
                class="result"
            >{{ result }}</pre>
            <div v-else
                 class="result"
                 v-html="result"></div>

            <ul v-if="cities && cities.length">
                <li v-for="city in cities" :key="city">{{ city }}</li>
            </ul>
        </div>
    </div>
</template>

<script>

    export default {
        components: {},
        data: () => ({
            query: "",
            result: "",
            cities: [],
            isPre: false
        }),
        computed: {},
        methods: {
            getResult() {
                if(!this.query){
                    alert('empty query!')
                    return
                }
                this.result = "";
                this.cities = [];
                axios.get('/parse', {
                    params: {
                        parseQuery: this.query
                    }
                }).then(res => {
                    console.log(res);
                    this.result = res.data.text;
                    this.cities = res.data.cities;
                }).catch(err => {
                    console.log(err);
                });
            }
        }
    }
</script>

<style>
    .result {
        margin-top: 20px;
        border: 1px solid #ccc;
        padding: 10px;
    }
</style>