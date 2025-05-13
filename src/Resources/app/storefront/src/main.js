import ApplePayPlugin from './betterpayment/apple-pay.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('ApplePayPlugin', ApplePayPlugin, '[data-apple-pay-plugin]');
