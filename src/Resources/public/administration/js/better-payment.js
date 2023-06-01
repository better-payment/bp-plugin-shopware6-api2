(this.webpackJsonp=this.webpackJsonp||[]).push([["better-payment"],{"49DC":function(t,e){t.exports='{% block sw_order_detail_base_line_items_card %}\r\n    {% parent %}\r\n    <sw-card\r\n            v-if="captureCardIsVisible"\r\n            :title="$tc(\'betterpayment.capture.cardTitle\')">\r\n        <template #grid>\r\n            <sw-card-section divider="bottom" :slim="true">\r\n                <sw-container>\r\n                    <sw-label variant="info" style="height: auto">\r\n                        <div style="white-space: normal;">\r\n                            <span v-if="paymentMethod == \'kar\'">\r\n                                {{ $tc(\'betterpayment.capture.labels.paymentMethodsInfo.invoice\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'kar_b2b\'">\r\n                                {{ $tc(\'betterpayment.capture.labels.paymentMethodsInfo.invoiceB2B\') }}\r\n                            </span>\r\n                        </div>\r\n                    </sw-label>\r\n                </sw-container>\r\n                <sw-container v-if="!isCapturableState">\r\n                    <sw-label variant="warning">\r\n                        {{ $tc(\'betterpayment.capture.labels.notCapturableState\') }}\r\n                    </sw-label>\r\n                </sw-container>\r\n                <sw-container columns="1fr 1fr" gap="0px 14px">\r\n                    <sw-container rows="1fr 1fr 1fr" gap="10px 0px">\r\n                        <sw-number-field\r\n                                required numberType="float" :digits="2" :allowEmpty="false" size="small"\r\n                                :disabled="!canCreateCapture"\r\n                                :label="$tc(\'betterpayment.capture.labels.amount\')"\r\n                                v-model="capture.amount">\r\n                        </sw-number-field>\r\n                        <sw-text-field\r\n                                required size="small"\r\n                                :disabled="!canCreateCapture"\r\n                                :label="$tc(\'betterpayment.capture.labels.invoiceId\')"\r\n                                v-model="capture.invoice_id">\r\n                        </sw-text-field>\r\n                        <sw-text-field\r\n                                :copyable="true" :copyableTooltip="true" disabled size="small"\r\n                                v-model="betterPaymentTransactionId"\r\n                                :label="$tc(\'betterpayment.capture.labels.transactionId\')">\r\n                        </sw-text-field>\r\n                    </sw-container>\r\n                    <sw-textarea-field\r\n                            type="textarea" size="small"\r\n                            :disabled="!canCreateCapture"\r\n                            :label="$tc(\'betterpayment.capture.labels.comment\')"\r\n                            v-model="capture.comment">\r\n                    </sw-textarea-field>\r\n                </sw-container>\r\n                <sw-button-process\r\n                        style="float: right; margin-top: 5px"\r\n                        :processSuccess="processSuccess"\r\n                        :disabled="buttonDisabled || !canCreateCapture"\r\n                        @click="createCapture"\r\n                        variant="ghost"\r\n                        size="small"\r\n                        @process-finish="createCaptureFinished">\r\n                    {{ $tc(\'betterpayment.capture.actions.createNew\') }}\r\n                </sw-button-process>\r\n            </sw-card-section>\r\n            <sw-data-grid\r\n                    :isLoading="isLoading"\r\n                    :showSelection="false" :showActions="false"\r\n                    :dataSource="captures" v-if="captures.length !== 0"\r\n                    :columns="[\r\n                        { property: \'amount\', label: $tc(\'betterpayment.capture.labels.amount\') },\r\n                        { property: \'message\', label: $tc(\'betterpayment.capture.labels.comment\') },\r\n                        {# TODO: parse it according to shop setting for datetime #}\r\n                        { property: \'created_at\', label: $tc(\'betterpayment.capture.labels.date\') }\r\n                    ]">\r\n            </sw-data-grid>\r\n        </template>\r\n    </sw-card>\r\n{% endblock %}'},"HTs+":function(t,e){t.exports='{% block sw_order_detail_base_line_items_card %}\r\n    {% parent %}\r\n    <sw-card\r\n            v-if="refundCardIsVisible"\r\n            :title="$tc(\'betterpayment.refund.cardTitle\')">\r\n        <template #grid>\r\n            <sw-card-section divider="bottom" :slim="true">\r\n                <sw-container v-if="isFullyRefunded">\r\n                    <sw-label variant="success">\r\n                        {{ $tc(\'betterpayment.refund.labels.noMoreRefunds\') }}\r\n                    </sw-label>\r\n                </sw-container>\r\n                <sw-container>\r\n                    <sw-label variant="info" style="height: auto">\r\n                        <div style="white-space: normal;">\r\n                            <span v-if="paymentMethod == \'cc\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.creditCard\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'sofort\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.sofort\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'paydirekt\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.paydirekt\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'paypal\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.paypal\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'dd\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.sepaDirectDebit\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'dd_b2b\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.sepaDirectDebitB2B\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'kar\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.invoice\') }}\r\n                            </span>\r\n                            <span v-else-if="paymentMethod == \'kar_b2b\'">\r\n                                {{ $tc(\'betterpayment.refund.labels.paymentMethodsInfo.invoiceB2B\') }}\r\n                            </span>\r\n                        </div>\r\n                    </sw-label>\r\n                </sw-container>\r\n                <sw-container columns="1fr 1fr" gap="0px 14px">\r\n                    <sw-container rows="1fr 1fr 1fr" gap="10px 0px">\r\n                        <sw-number-field\r\n                                required numberType="float" :digits="2" :allowEmpty="false" size="small"\r\n                                :disabled="!canCreateRefund || isFullyRefunded"\r\n                                :label="$tc(\'betterpayment.refund.labels.amount\')"\r\n                                v-model="refund.amount">\r\n                        </sw-number-field>\r\n                        <sw-datepicker\r\n                                size="small" :label="$tc(\'betterpayment.refund.labels.date\')"\r\n                                :disabled="!canCreateRefund || isFullyRefunded"\r\n                                v-model="refund.execution_date">\r\n                        </sw-datepicker>\r\n                        <sw-text-field\r\n                                :copyable="true" :copyableTooltip="true" disabled size="small"\r\n                                :label="$tc(\'betterpayment.refund.labels.transactionId\')"\r\n                                v-model="betterPaymentTransactionId">\r\n                        </sw-text-field>\r\n                    </sw-container>\r\n                    <sw-textarea-field\r\n                            type="textarea" size="small"\r\n                            :disabled="!canCreateRefund || isFullyRefunded"\r\n                            :label="$tc(\'betterpayment.refund.labels.comment\')"\r\n                            v-model="refund.comment">\r\n                    </sw-textarea-field>\r\n                </sw-container>\r\n                <sw-button-process\r\n                        style="float: right"\r\n                        :processSuccess="processSuccess"\r\n                        :disabled="buttonDisabled || !canCreateRefund || isFullyRefunded"\r\n                        @click="createRefund"\r\n                        variant="ghost"\r\n                        size="small"\r\n                        @process-finish="createRefundFinished">\r\n                    {{ $tc(\'betterpayment.refund.actions.createNew\') }}\r\n                </sw-button-process>\r\n            </sw-card-section>\r\n            <sw-data-grid\r\n                    :isLoading="isLoading"\r\n                    :showSelection="false" :showActions="false"\r\n                    :dataSource="refunds" v-if="refunds.length !== 0"\r\n                    :columns="[\r\n                        { property: \'amount\', label: $tc(\'betterpayment.refund.labels.amount\') },\r\n                        { property: \'message\', label: $tc(\'betterpayment.refund.labels.comment\') },\r\n                        {# TODO: parse it according to shop setting for datetime #}\r\n                        { property: \'created_at\', label: $tc(\'betterpayment.refund.labels.date\') }\r\n                    ]">\r\n            </sw-data-grid>\r\n        </template>\r\n    </sw-card>\r\n{% endblock %}'},Ognn:function(t){t.exports=JSON.parse('{"betterpayment":{"test":{"api_url":"https://testapi.betterpayment.de","dash_url":"https://testdashboard.betterpayment.de"},"production":{"api_url":"https://api.betterpayment.de","dash_url":"https://dashboard.betterpayment.de"}},"solvendi":{"test":{"api_url":"https://testapi-solvendi.betterpayment.de","dash_url":"https://testdashboard-solvendi.betterpayment.de"},"production":{"api_url":"https://api-solvendi.betterpayment.de","dash_url":"https://dashboard-solvendi.betterpayment.de"}},"diagonal":{"test":{"api_url":"https://testapi.diagonal-payment.eu","dash_url":"https://testdashboard.diagonal-payment.eu"},"production":{"api_url":"https://api.diagonal-payment.eu","dash_url":"https://dashboard.diagonal-payment.eu"}},"collectai":{"test":{"api_url":"https://testapi-collectai.betterpayment.de","dash_url":"https://testdashboard-collectai.betterpayment.de"},"production":{"api_url":"https://api-collectai.betterpayment.de","dash_url":"https://dashboard-collectai.betterpayment.de"}},"betterbill":{"test":{"api_url":"https://testapi.betterbill.net","dash_url":"https://testdashboard.betterbill.net"},"production":{"api_url":"https://api.betterbill.net","dash_url":"https://dashboard.betterbill.net"}},"kleverpay":{"test":{"api_url":"https://testapi.kleverpay.de","dash_url":"https://testdashboard.kleverpay.de"},"production":{"api_url":"https://api.kleverpay.de","dash_url":"https://dashboard.kleverpay.de"}},"abilitapay":{"test":{"api_url":"https://testapi.abilitapay.de","dash_url":"https://testdashboard.abilitapay.de"},"production":{"api_url":"https://api.abilitapay.de","dash_url":"https://dashboard.abilitapay.de"}},"vr_dienste":{"test":{"api_url":"https://testapi.vr-jetztzahlen.de","dash_url":"https://testdashboard.vr-jetztzahlen.de"},"production":{"api_url":"https://api.vr-jetztzahlen.de","dash_url":"https://dashboard.vr-jetztzahlen.de"}},"vr_straubing":{"test":{"api_url":"https://testapi-raiffeisenbank-straubing.betterpayment.de","dash_url":"https://testdashboard-raiffeisenbank-straubing.betterpayment.de"},"production":{"api_url":"https://api-raiffeisenbank-straubing.betterpayment.de","dash_url":"https://dashboard-raiffeisenbank-straubing.betterpayment.de"}},"continentalpay":{"test":{"api_url":"https://testapi.continentalpay.com","dash_url":"https://testdashboard.continentalpay.com"},"production":{"api_url":"https://api.continentalpay.com","dash_url":"https://dashboard.continentalpay.com"}},"vrkg":{"test":{"api_url":"https://testapi-vrkg.betterpayment.de","dash_url":"https://testdashboard-vrkg.betterpayment.de"},"production":{"api_url":"https://api-vrkg.betterpayment.de","dash_url":"https://dashboard-vrkg.betterpayment.de"}},"demondo":{"test":{"api_url":"https://testapi.demondo-paygate.com","dash_url":"https://testdashboard.demondo-paygate.com"},"production":{"api_url":"https://api.demondo-paygate.com","dash_url":"https://dashboard.demondo-paygate.com"}},"vivapago":{"test":{"api_url":"https://testapi.vivapago.net","dash_url":"https://testdashboard.vivapago.net"},"production":{"api_url":"https://api.vivapago.net","dash_url":"https://dashboard.vivapago.net"}}}')},uZ8U:function(t,e,a){"use strict";a.r(e);var r=a("HTs+"),n=a.n(r),s=a("Ognn");const{Component:i,Mixin:o,ApiService:d}=Shopware;i.override("sw-order-detail-base",{template:n.a,inject:["orderStateMachineService"],mixins:[o.getByName("notification")],data:()=>({refund:{amount:null,comment:null,execution_date:null},refunds:[],processSuccess:!1,buttonDisabled:!1,apiUrl:null,apiAuth:null}),created(){this.setAPIProperties()},computed:{isBetterPaymentTransaction(){return null!==this.transaction.customFields&&this.transaction.customFields.hasOwnProperty("better_payment_transaction_id")},betterPaymentTransactionId(){return this.isBetterPaymentTransaction?this.transaction.customFields.better_payment_transaction_id:null},refundCardIsVisible(){return this.isBetterPaymentTransaction},isRefundable(){return["paid","paid_partially","refunded_partially"].includes(this.transaction.stateMachineState.technicalName)},isFullyRefunded(){return"refunded"===this.transaction.stateMachineState.technicalName},canCreateRefund(){return this.isRefundable},paymentMethod(){return this.transaction.paymentMethod.customFields.shortname}},watch:{order(){this.refundCardIsVisible&&this.getRefunds()}},methods:{setAPIProperties(){d.getByName("systemConfigApiService").getValues("BetterPayment").then((t=>{const e=t["BetterPayment.config.environment"],a=t["BetterPayment.config.whiteLabel"],r=t["BetterPayment.config.testAPIKey"],n=t["BetterPayment.config.productionAPIKey"],i="test"===e?r:n,o=t["BetterPayment.config.testOutgoingKey"],d=t["BetterPayment.config.productionOutgoingKey"],p="test"===e?o:d;return this.apiUrl=s[a][e].api_url,this.apiAuth=btoa(i+":"+p),Promise.resolve()}))},getRefunds(){const t=this.apiUrl+"/rest/transactions/"+this.betterPaymentTransactionId+"/log",e=new Headers;e.append("Authorization","Basic "+this.apiAuth);fetch(t,{method:"GET",headers:e}).then((t=>t.json())).then((t=>{t.hasOwnProperty("error_code")?this.createNotificationError({message:t.error_message}):this.refunds=t.filter((t=>"refund"===t.type)).filter((t=>7===t.status))})).catch((t=>{this.createNotificationError({message:t})}))},createRefund(){this.buttonDisabled=!0;const t=this.apiUrl+"/rest/refund",e=new Headers;e.append("Authorization","Basic "+this.apiAuth),e.append("Content-Type","application/json");const a=JSON.stringify({transaction_id:this.betterPaymentTransactionId,amount:this.refund.amount,comment:this.refund.comment,execution_date:this.refund.execution_date});fetch(t,{method:"POST",headers:e,body:a}).then((t=>t.json())).then((t=>{this.buttonDisabled=!1,0===t.error_code?"error"!==t.status?(this.getRefunds(),this.processSuccess=!0,this.createNotificationSuccess({message:this.$tc("betterpayment.refund.messages.successfulRefundRequest")}),this.updateTransactionState()):this.createNotificationError({message:this.$tc("betterpayment.refund.messages.invalidRefundRequest")}):this.createNotificationError({message:t.error_message})})).catch((t=>{this.createNotificationError({message:t})}))},createRefundFinished(){this.refund.amount=null,this.refund.comment=null,this.refund.execution_date=null,this.processSuccess=!1},updateTransactionState(){const t=this.apiUrl+"/rest/transactions/"+this.betterPaymentTransactionId,e=new Headers;e.append("Authorization","Basic "+this.apiAuth);fetch(t,{method:"GET",headers:e}).then((t=>t.json())).then((t=>{if(t.hasOwnProperty("error_code"))this.createNotificationError({message:t.error_message});else if(t.refunded_amount>0){let e;e=t.refunded_amount>=t.amount?"refund":"refund_partially";const a=[],r=!0;this.orderStateMachineService.transitionOrderTransactionState(this.transaction.id,e,{documentIds:a,sendMail:r}).then((()=>{this.$emit("order-state-change")})).catch((t=>{this.createNotificationError(t)}))}})).catch((t=>{this.createNotificationError({message:t})}))}}});var p=a("49DC"),c=a.n(p);const{Component:l,Mixin:u,ApiService:h}=Shopware;l.override("sw-order-detail-base",{template:c.a,inject:["orderStateMachineService"],mixins:[u.getByName("notification")],data(){return{capture:{amount:null,invoice_id:null,comment:this.$tc("betterpayment.capture.defaults.comment")},captures:[],processSuccess:!1,buttonDisabled:!1,apiUrl:null,apiAuth:null}},created(){this.setAPIProperties()},computed:{isBetterPaymentTransaction(){return null!==this.transaction.customFields&&this.transaction.customFields.hasOwnProperty("better_payment_transaction_id")},betterPaymentTransactionId(){return this.isBetterPaymentTransaction?this.transaction.customFields.better_payment_transaction_id:null},isCapturablePaymentMethod(){return["kar","kar_b2b"].includes(this.paymentMethod)},captureCardIsVisible(){return this.isBetterPaymentTransaction&&this.isCapturablePaymentMethod},isCapturableState(){return["in_progress","paid_partially","paid"].includes(this.transaction.stateMachineState.technicalName)},canCreateCapture(){return this.isCapturableState},paymentMethod(){return this.transaction.paymentMethod.customFields.shortname}},watch:{order(){this.captureCardIsVisible&&this.getCaptures()}},methods:{setAPIProperties(){h.getByName("systemConfigApiService").getValues("BetterPayment").then((t=>{const e=t["BetterPayment.config.environment"],a=t["BetterPayment.config.whiteLabel"],r=t["BetterPayment.config.testAPIKey"],n=t["BetterPayment.config.productionAPIKey"],i="test"===e?r:n,o=t["BetterPayment.config.testOutgoingKey"],d=t["BetterPayment.config.productionOutgoingKey"],p="test"===e?o:d;return this.apiUrl=s[a][e].api_url,this.apiAuth=btoa(i+":"+p),Promise.resolve()}))},getCaptures(){const t=this.apiUrl+"/rest/transactions/"+this.betterPaymentTransactionId+"/log",e=new Headers;e.append("Authorization","Basic "+this.apiAuth);fetch(t,{method:"GET",headers:e}).then((t=>t.json())).then((t=>{t.hasOwnProperty("error_code")?this.createNotificationError({message:t.error_message}):this.captures=t.filter((t=>"capture"===t.type)).filter((t=>[1,2,3].includes(t.status)))})).catch((t=>{this.createNotificationError({message:t})}))},createCapture(){this.buttonDisabled=!0;const t=this.apiUrl+"/rest/capture",e=new Headers;e.append("Authorization","Basic "+this.apiAuth),e.append("Content-Type","application/json");const a=JSON.stringify({transaction_id:this.betterPaymentTransactionId,amount:this.capture.amount,invoice_id:this.capture.invoice_id,comment:this.capture.comment});fetch(t,{method:"POST",headers:e,body:a}).then((t=>t.json())).then((t=>{this.buttonDisabled=!1,0===t.error_code?"error"!==t.status?(this.getCaptures(),this.processSuccess=!0,this.createNotificationSuccess({message:this.$tc("betterpayment.capture.messages.successfulCaptureRequest")})):this.createNotificationError({message:this.$tc("betterpayment.capture.messages.invalidCaptureRequest")}):this.createNotificationError({message:t.error_message})})).catch((t=>{this.createNotificationError({message:t})}))},createCaptureFinished(){this.capture.amount=null,this.capture.invoice_id=null,this.capture.comment=this.$tc("betterpayment.capture.defaults.comment"),this.processSuccess=!1}}})}},[["uZ8U","runtime"]]]);