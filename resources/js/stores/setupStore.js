import { create } from 'zustand';

export const useSetupStore = create((set) => ({
    activeTab: 'structure',
    setActiveTab: (activeTab) => set({ activeTab }),
}));
