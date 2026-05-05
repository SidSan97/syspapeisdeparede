import axios from 'axios';

const http = axios.create({
  baseURL: import.meta.env.APP_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

export { http };
