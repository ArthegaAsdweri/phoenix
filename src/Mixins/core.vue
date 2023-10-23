<template>
</template>

<script>
export default {
    name: "coreMixin",
    methods: {
        /**
         * Diese Methode ist der globale Handler für alle Ajax-Requests
         *
         * @param string path              Das Skript, das aufgerufen werden soll
         * @param object params            ein Objekt mit Post-Parametern, die an das Skript gesendet werden sollen
         * @param object successActions    ein Objekt mit weiterführenden Parametern, die Success-Aktionen starten
         */
        sendAjax(path, params, successActions) {
            axios.post(path, params, {
                headers: {
                    AJAX: 'true'
                }
            }).then(response => {
                if (typeof (response.data.debug) !== 'undefined') {
                    let debugQueries = response.data.debug;
                    
                    //FIXME - löschen von Daten aus Vuetify Tabellen funktioniert damit nicht - spannend!
                    vm.ajaxRequests.push(debugQueries);
                    if (vm.ajaxRequests.length > 3) {
                        vm.ajaxRequests.shift();
                    }
                }
                if (successActions && successActions.callMethod) {
                    let methodCall = successActions.callMethod;
                    let method = methodCall.method;
                    
                    let content = response.data.content;
                    if (methodCall.responseType === 'json') {
                        content = JSON.parse(content);
                    }
                    
                    if (methodCall.scope === 'global') {
                        if (methodCall.passResponse === true) {
                            vm[method](content);
                        } else {
                            vm[method]();
                        }
                    } else {
                        if (methodCall.passResponse === true) {
                            this[method](content);
                        } else {
                            this[method]();
                        }
                    }
                }
            }).catch(function (error) {
                //Fehler
                console.log(error.message);
            });
        },
        submitAjax: function (script, params, successActions) {
            this.sendAjax('/' + script, params, successActions);
        },
        submitGlobalAjax: function (script, params, successActions) {
            this.sendAjax('/ajax_global/' + script, params, successActions);
        }
    }
};
</script>

<style scoped>
</style>