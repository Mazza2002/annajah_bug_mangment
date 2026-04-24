import api from './api';

export const bugService = {
    getAll(params = {}) {
        return api.get('/bugs', { params });
    },
    getById(id) {
        return api.get(`/bugs/${id}`);
    },
    create(data) {
        return api.post('/bugs', data);
    },
    updateStatus(id, status, notes = '') {
        return api.patch(`/bugs/${id}/status`, { status, notes });
    },
    assign(id, userId) {
        return api.patch(`/bugs/${id}/assign`, { assigned_to: userId });
    },
    delete(id) {
        return api.delete(`/bugs/${id}`);
    }
};
