import { http } from '@/lib/http';

const endpoint = '/v1/wallet';

export const walletService = {
    async getBalance() {
        const { data } = await http.get(`${endpoint}`);
        return data.balance;
    },
};
