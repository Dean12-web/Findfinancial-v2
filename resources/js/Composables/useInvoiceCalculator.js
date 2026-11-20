import { computed } from "vue";

export function useInvoiceCalculator({ 
    items,
    masterDiscountType,
    masterDiscountInput,
    shippingCost,
    paidAmount,
    customer,
    pointRules
}) {
    const grossSubtotal = computed(() => 
        items.value.reduce((sum,item) => sum + (Number(item.price) || 0) * (Number(item.quantity) || 0),0)
    );

    const lineDiscountTotal = computed(()=>
        items.value.reduce((sum, item)=>{
          const rowGross = (Number(item.price) || 0) * (Number(item.quantity)||0);
          const discVal = Number(item.discount) || 0;
          if(discVal <= 0) return sum;
          
          const safeDisc = Math.max(0, discVal);
          const cut = item.discount_type === 'percentage'
              ? (rowGross * Math.min(100, safeDisc)) / 100
              : Math.min(rowGross, safeDisc * (Number(item.quantity) || 0));

          return sum + Math.min(cut, rowGross);
        },0)
    );

    const totalItemNet = computed(() => Math.max(0, grossSubtotal.value - lineDiscountTotal.value));

    const masterDiscountAmount = computed(() => {
        const val = Number(masterDiscountInput.value) || 0;
        if(val <= 0) return 0;

        let cut = 0;
        if(masterDiscountType.value === 'amount'){
            cut = val;
        }else if(masterDiscountType.value === 'percentage'){
            cut = (val * totalItemNet.value) / 100;
        }else if(masterDiscountType.value === 'point'){
            const usedUnit = Number(pointRules.value?.used_points_unit || 0);
            const maxMemberPts = Number(customer.value?.points || 0);
            const cappedPts = maxMemberPts > 0 ? Math.min(val, maxMemberPts) : 0;
            cut = cappedPts * usedUnit;
        }

        return Math.min(cut, totalItemNet.value);
    });

    const grandTotal = computed(() =>
        Math.max(0, totalItemNet.value - masterDiscountAmount.value) + Math.max(0, Number(shippingCost.value) || 0)
    );

    const pointsEarned = computed(() => {
        if(lineDiscountTotal.value > 0 || masterDiscountAmount.value > 0) return 0;
        const earnUnit = Number(pointRules.value?.points_unit || 0);
        if(earnUnit <= 0) return 0;
        return Math.floor(totalItemNet.value / earnUnit);
    });

    const remainingBalance = computed(() => Math.max(0, grandTotal.value - (Number(paidAmount.value) || 0)));

    const changeAmount = computed(() => Math.max(0, (Number(paidAmount.value) || 0) - grandTotal.value));

    const formatRupiah = (val) => 
        new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR', maximumFractionDigits:0}).format(val || 0);


    return {
        grossSubtotal,
        lineDiscountTotal,
        totalItemNet,
        masterDiscountAmount,
        grandTotal,
        pointsEarned,
        remainingBalance,
        changeAmount,
        formatRupiah
    };
}