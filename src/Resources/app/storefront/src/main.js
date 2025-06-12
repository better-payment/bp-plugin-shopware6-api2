import ApplePayPlugin from './betterpayment/apple-pay.plugin';
import GooglePayPlugin from './betterpayment/google-pay.plugin'

const PluginManager = window.PluginManager;
PluginManager.register('ApplePayPlugin', ApplePayPlugin, '[data-apple-pay-plugin]');
PluginManager.register('GooglePayPlugin', GooglePayPlugin, '[data-google-pay-plugin]');