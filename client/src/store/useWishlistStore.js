import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useWishlistStore = create(
  persist(
    (set) => ({
      wishlist: [],
      toggleWishlist: (product) => set((state) => {
        const exists = state.wishlist.find((item) => item.id === product.id);
        
        if (exists) {
          return { wishlist: state.wishlist.filter((item) => item.id !== product.id) };
        }
        return { wishlist: [...state.wishlist, product] };
      }),
      clearWishlist: () => set({ wishlist: [] }),
    }),
    {
      name: 'classy-wishlist', 
    }
  )
);