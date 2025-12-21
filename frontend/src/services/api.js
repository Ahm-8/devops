import axios from 'axios';
import { getAccessToken } from './auth';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
});

// Add auth token to requests
api.interceptors.request.use((config) => {
  const token = getAccessToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Rooms API
export const getRooms = async (location) => {
  const response = await api.get('/rooms', { params: { location } });
  return response.data;
};

export const getAvailableRooms = async (location, date) => {
  const response = await api.get('/rooms/available', {
    params: { location, date },
  });
  return response.data;
};

// Bookings API
export const getBookings = async (userId = null) => {
  const params = userId ? { userId } : {};
  const response = await api.get('/bookings', { params });
  return response.data;
};

export const getPriceBreakdown = async (location, date, roomName) => {
  const response = await api.get('/bookings/price-breakdown', {
    params: { location, date, roomName },
  });
  return response.data;
};

export const createBooking = async (location, date, roomName) => {
  const response = await api.post('/bookings', {
    location,
    date,
    roomName,
  });
  return response.data;
};

export const deleteBooking = async (location, date, roomName) => {
  const response = await api.delete('/bookings', {
    data: { location, date, roomName },
  });
  return response.data;
};
