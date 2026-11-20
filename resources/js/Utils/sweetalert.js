import Swal from 'sweetalert2';

const isDarkMode = () =>
    typeof document !== 'undefined' &&
    document.documentElement.classList.contains('dark');

export const getSwalTheme = () => ({
    background: isDarkMode() ? '#16191f' : '#ffffff',
    color: isDarkMode() ? '#f1f5f9' : '#0f172a',
    confirmButtonColor: '#ff5500',
    cancelButtonColor: '#64748b',
});

// Toast Notifikasi (Notify)
export const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
});

export const notifySuccess = (message) => {
    const theme = getSwalTheme();
    return Toast.fire({
        icon: 'success',
        title: message,
        background: theme.background,
        color: theme.color,
    });
};

export const notifyWarning = (message) => {
    const theme = getSwalTheme();
    return Toast.fire({
        icon: 'warning',
        title: message,
        background: theme.background,
        color: theme.color,
    });
};

export const notifyError = (message) => {
    const theme = getSwalTheme();
    return Toast.fire({
        icon: 'error',
        title: message,
        background: theme.background,
        color: theme.color,
    });
};

export const notifyInfo = (message) => {
    const theme = getSwalTheme();
    return Toast.fire({
        icon: 'info',
        title: message,
        background: theme.background,
        color: theme.color,
    });
};

// Modal Dialog Konfirmasi Transaksi
export const confirmTransaction = async ({
    buyerType,
    buyerName,
    itemCount,
    grandTotalFormatted,
    paidFormatted,
    remainingFormatted,
    pointsUsedText,
    pointsEarnedText,
}) => {
    const theme = getSwalTheme();
    const isStore = buyerType === 'store';
    const typeLabel = isStore ? 'Pasokan Antar-Toko (B2B)' : 'Penjualan Konsumen (B2C)';

    return Swal.fire({
        title: 'Konfirmasi Transaksi',
        html: `
            <div style="text-align: left; font-size: 13px; line-height: 1.6; padding: 4px 8px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(148,163,184,0.2); padding-bottom: 6px; margin-bottom: 8px;">
                    <span style="opacity: 0.7;">Tipe Transaksi:</span>
                    <strong style="color: ${isStore ? '#3b82f6' : '#ff5500'};">${typeLabel}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="opacity: 0.7;">Tujuan:</span>
                    <strong>${buyerName || '-'}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="opacity: 0.7;">Jumlah Produk:</span>
                    <strong>${itemCount} item</strong>
                </div>
                ${pointsUsedText ? `
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="opacity: 0.7;">Tukar Poin:</span>
                    <strong style="color: #f43f5e;">${pointsUsedText}</strong>
                </div>` : ''}
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="opacity: 0.7;">Total Tagihan:</span>
                    <strong style="font-size: 15px; color: #ff5500;">${grandTotalFormatted}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="opacity: 0.7;">Dibayar:</span>
                    <strong>${paidFormatted}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="opacity: 0.7;">Sisa Tagihan:</span>
                    <strong>${remainingFormatted}</strong>
                </div>
                ${pointsEarnedText ? `
                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(148,163,184,0.2); padding-top: 6px; margin-top: 6px;">
                    <span style="opacity: 0.7;">Estimasi Poin:</span>
                    <strong style="color: #10b981;">${pointsEarnedText}</strong>
                </div>` : ''}
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Proses Sekarang',
        cancelButtonText: 'Periksa Kembali',
        confirmButtonColor: theme.confirmButtonColor,
        cancelButtonColor: theme.cancelButtonColor,
        background: theme.background,
        color: theme.color,
        reverseButtons: true,
    });
};

// Modal Sukses Transaksi
export const showSuccessTransaction = async ({ message, orderId, onViewOrder }) => {
    const theme = getSwalTheme();
    return Swal.fire({
        title: 'Transaksi Berhasil!',
        text: message || 'Faktur transaksi telah berhasil diproses.',
        icon: 'success',
        showCancelButton: Boolean(orderId),
        confirmButtonText: orderId ? 'Lihat Faktur' : 'OK',
        cancelButtonText: 'Tutup',
        confirmButtonColor: theme.confirmButtonColor,
        cancelButtonColor: theme.cancelButtonColor,
        background: theme.background,
        color: theme.color,
    }).then((res) => {
        if (res.isConfirmed && orderId && typeof onViewOrder === 'function') {
            onViewOrder(orderId);
        }
    });
};

export default Swal;

