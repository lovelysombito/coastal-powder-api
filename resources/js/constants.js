import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');


const API_URL = process.env.MIX_APP_URL;

export const getDealLineItems = (userId, dealId, signature) => axios.get(`${API_URL}/api/hubspot/crm-cards/deals/${dealId}/line-items`, {
    headers: {
        'crmcard-signature': signature,
        'crmcard-user-id': userId,
    }
});

export const getProducts = (userId, signature) => axios.get(`${API_URL}/api/hubspot/crm-cards/products`, {
    headers: {
        'crmcard-signature': signature,
        'crmcard-user-id': userId,
    }
});

export const getColours = (userId, signature) => axios.get(`${API_URL}/api/hubspot/crm-cards/colours`, {
    headers: {
        'crmcard-signature': signature,
        'crmcard-user-id': userId,
    }
});

export const saveChanges = (userId, dealId, data, signature) => axios.post(`${API_URL}/api/hubspot/crm-cards/deals/${dealId}/line-items`, data, {
    headers: {
        'crmcard-signature': signature,
        'crmcard-user-id': userId,
    }
});

export const materials = ["Aluminium", "Steel"];
export const aluminiumTreatments = ["S", "ST", "STC", "STPC", "SBTPC", "T", "TC", "TP", "TPC", "C"];
export const steelTreatments = ["F", "FB", "FBP", "FBPC", "B", "BPC", "BC", "BP", "C"];
export const coatingLine = ["Small Batch", "Main Line", "Big Batch", "No Line"];

export const currencyFormat = (num) => {
    return '$' + num.toFixed(2);
}