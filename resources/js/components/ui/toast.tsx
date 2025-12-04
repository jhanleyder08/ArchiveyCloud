import { toast as sonnerToast } from "sonner";

// Wrapper para toasts con estilos consistentes
export const toast = {
  success: (message: string) => {
    sonnerToast.success(message, {
      style: {
        background: '#10b981',
        color: '#ffffff',
        border: 'none',
      },
    });
  },

  error: (message: string) => {
    sonnerToast.error(message, {
      style: {
        background: '#ef4444',
        color: '#ffffff', 
        border: 'none',
      },
    });
  },

  info: (message: string) => {
    sonnerToast.info(message, {
      style: {
        background: '#3b82f6',
        color: '#ffffff',
        border: 'none',
      },
    });
  },

  warning: (message: string) => {
    sonnerToast.warning(message, {
      style: {
        background: '#f59e0b',
        color: '#ffffff',
        border: 'none',
      },
    });
  },
};

export { Toaster } from "sonner";
