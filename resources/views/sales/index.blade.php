@extends('reception.layout')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://unpkg.com/____IMPORT___" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script id="reception-visits-data" type="application/json">@json($recentReceptionVisits)</script>
<script id="unpaid-tickets-data" type="application/json">@json($unpaidItTickets)</script>
<style>
 [x-cloak]{display:none!important}
 .cds--tile, .cds--data-table-container, .cds--btn, .cds--tag { border-radius:0 !important; }
 .ibm-grid { max-width:1584px; margin:0 auto; padding:0 1rem; }
 @media(min-width:66rem){ .ibm-grid{ padding:0 2rem; } }
 .ibm-hgrid-4 { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
 .ibm-hgrid-2 { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:1rem; margin-bottom:1rem; }
 @media(max-width:66rem){ .ibm-hgrid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
 @media(max-width:42rem){ .ibm-hgrid-4, .ibm-hgrid-2 { grid-template-columns: 1fr; } }
</style>

<div x-data="{
 activeTab: '{{ $currentTab }}',
 searchQuery: '',
 selectedCategory: 'All',
 tableSearch: '',
 tableFilterStatus: 'all',
 selectedRows: [],
 selectAllRows: false,
  showPosModal: false,
 showSaleSuccess: @js((bool) session('receipt_id')),
 showQuoteModal: false,
 showCustomItemModal: false,
 liveStatsLoading: false,
 lastSyncTime: '{{ now()->format('H:i:s') }}',
 metrics: {
 todaySalesTotal: {{ (int) $todaySalesTotal }},
 yesterdaySalesTotal: {{ (int) $yesterdaySalesTotal }},
 thisWeekSalesTotal: {{ (int) $thisWeekSalesTotal }},
 thisMonthSalesTotal: {{ (int) $thisMonthSalesTotal }},
 growthPct: {{ (float) $growthPct }},
 monthlyTarget: {{ (int) $monthlyTarget }},
 targetPct: {{ (float) $targetPct }},
 todayTransactionsCount: {{ (int) $todayTransactionsCount }},
 avgReceiptValue: {{ (int) $avgReceiptValue }},
 pendingBillsCount: {{ (int) $pendingBillsCount }},
 pendingBillsAmount: {{ (int) $pendingBillsAmount }},
 totalItemsInStock: {{ (int) $totalItemsInStock }},
 lowStockCount: {{ (int) $lowStockCount }}
 },
 liquidity: {
 todayCashTotal: {{ (int) $todayCashTotal }},
 todayMpesaTotal: {{ (int) $todayMpesaTotal }},
 todayCardTotal: {{ (int) $todayCardTotal }},
 todayBankTotal: {{ (int) $todayBankTotal }},
 cashPct: {{ (int) $cashPct }},
 mpesaPct: {{ (int) $mpesaPct }},
 cardPct: {{ (int) $cardPct }},
 bankPct: {{ (int) $bankPct }}
 },
 receptionVisits: JSON.parse(document.getElementById('reception-visits-data').textContent || '[]'),
 unpaidTickets: JSON.parse(document.getElementById('unpaid-tickets-data').textContent || '[]'),
 toggleSelectAll(items){ if(this.selectAllRows){this.selectedRows=items.map(i=>i.id);}else{this.selectedRows=[];} },
 init(){ this.startLivePolling(); },
 startLivePolling(){ setInterval(()=>{ this.fetchLiveStats(); },8000); },
 fetchLiveStats(){
 this.liveStatsLoading=true;
 fetch('{{ route('sales.api.live_stats') }}',{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
 .then(r=>{if(!r.ok)throw new Error('Network');return r.json();})
 .then(data=>{
 this.liveStatsLoading=false;
 if(data && data.status==='success'){
 if(data.metrics) this.metrics=data.metrics;
 if(data.liquidity) this.liquidity=data.liquidity;
 if(data.recentReceptionVisits) this.receptionVisits=data.recentReceptionVisits;
 if(data.unpaidItTickets) this.unpaidTickets=data.unpaidItTickets;
 if(data.timestamp) this.lastSyncTime=data.timestamp;
 if(window.salesVelocityChart && data.weeklyTrend){
 window.salesVelocityChart.data.labels=data.weeklyTrend.map(d=>d.day+' ('+d.date+')');
 window.salesVelocityChart.data.datasets[0].data=data.weeklyTrend.map(d=>d.amount);
 window.salesVelocityChart.update();
 }
 }
 }).catch(()=>{this.liveStatsLoading=false;});
 },
 formatCurrency(val){return Number(val||0).toLocaleString();},
 posCart:[], posCustomerName:'Walk-in Customer', posCustomerPhone:'', posCompany:'', posPayMethod:'cash', posPayRef:'', posTendered:0, posDiscountPct:0, posApplyTax:false, posNotes:'', posLinkedTicketId:null,
 customItem:{name:'',price:0,qty:1,category:'General'},
 showStockAlert:false, stockAlertMessage:'',
 showSalesFeedback:false, salesFeedbackTitle:'', salesFeedbackMessage:'', salesFeedbackIsError:false,
 showCompleteModal:false, completeVisit:null, completePrice:'', completeNotes:'', completePriceError:'',
 openStockAlert(msg){ this.stockAlertMessage=msg; this.showStockAlert=true; this.$nextTick(()=>{ this.$refs.stockAlertClose && this.$refs.stockAlertClose.focus(); }); },
 closeStockAlert(){ this.showStockAlert=false; },
 openSalesFeedback(title, message, isError=false){ this.salesFeedbackTitle=title; this.salesFeedbackMessage=message; this.salesFeedbackIsError=isError; this.showSalesFeedback=true; this.$nextTick(()=>{ this.$refs.salesFeedbackClose && this.$refs.salesFeedbackClose.focus(); }); },
 closeSalesFeedback(){ this.showSalesFeedback=false; },
 openCompleteModal(visit){ if(!visit.service_id){ this.openSalesFeedback('Missing service','No SALES service found for this visit',true); return; } this.completeVisit=visit; this.completePrice=(visit.service_price && visit.service_price>0 ? String(visit.service_price) : '50000'); this.completeNotes='Completed by Sales'; this.completePriceError=''; this.showCompleteModal=true; this.$nextTick(()=>{ this.$refs.completePriceInput && this.$refs.completePriceInput.focus(); }); },
 closeCompleteModal(){ this.showCompleteModal=false; this.completeVisit=null; },
 async submitCompleteModal(){
   if(!this.completeVisit || !this.completeVisit.service_id) return;
   let price=parseInt(String(this.completePrice).replace(/[^0-9]/g,''),10);
   if(!price||price<=0){ this.completePriceError='Enter a valid amount (TZS)'; return; }
   this.completePriceError='';
   try{
     let res=await fetch('/api/live/services/'+this.completeVisit.service_id+'/complete',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''},body:JSON.stringify({price:price,resolution_notes:this.completeNotes||''})});
     let data=await res.json();
     if(data.success||res.ok){ this.closeCompleteModal(); this.openSalesFeedback('Completed','Completed: TZS '+Number(price).toLocaleString()+' added. Invoice pending_payment created.',false); this.fetchLiveStats(); }
     else{ this.openSalesFeedback('Complete failed', data.message||'Complete failed', true); }
   }catch(e){ this.openSalesFeedback('Complete error','Complete error: '+e.message, true); }
 },
  addToCart(product){
  if(!product.is_service && product.stock_quantity !== null && product.stock_quantity !== undefined){
    let stock = Number(product.stock_quantity);
    if(stock <= 0){ this.openStockAlert('Out of stock: ' + product.name + ' (0 available) — fetch from inventory'); return; }
    let existing = this.posCart.find(i=>i.id===product.id && i.name===product.name);
    let currentQty = existing ? Number(existing.qty) : 0;
    if(currentQty + 1 > stock){ this.openStockAlert('Insufficient stock for ' + product.name + ': only ' + stock + ' available, already ' + currentQty + ' in cart'); return; }
  }
  let e=this.posCart.find(i=>i.id===product.id && i.name===product.name);
  if(e){e.qty++;}else{this.posCart.push({id:product.id||null,name:product.name,sku:product.sku||'',price:Number(product.price),qty:1,is_service:product.is_service||false, stock_quantity: product.stock_quantity});}
  this.updateTendered();
  },
 addCustomItemToCart(){
 if(!this.customItem.name || this.customItem.price<=0) return;
 this.posCart.push({id:null,name:this.customItem.name,sku:'CUSTOM',price:Number(this.customItem.price),qty:Number(this.customItem.qty||1),is_service:true});
 this.customItem={name:'',price:0,qty:1,category:'General'}; this.showCustomItemModal=false; this.updateTendered();
 },
 loadItTicketToCart(ticket){
 this.posCart=[{id:null,name:ticket.title+' ('+(ticket.category||'IT Repair')+')',sku:ticket.ticket_code||('TKT-'+ticket.id),price:Number(ticket.price||50000),qty:1,is_service:true}];
 this.posCustomerName=ticket.visitor_name||'Walk-in Guest'; this.posCustomerPhone=ticket.visitor_phone||''; this.posCompany=ticket.visitor_company||''; this.posLinkedTicketId=ticket.id; this.activeTab='register'; this.showPosModal=false; this.updateTendered();
 },
 loadVisitToCart(visit){
 this.posCustomerName=visit.visitor||visit.full_name||'Walk-in Guest'; this.posCustomerPhone=visit.visitor_phone||visit.phone||''; this.posCompany=visit.organization_name||visit.company||'';
 let svcName=visit.service_name||visit.purpose||'FrontDesk Visit'; let svcPrice=Number(visit.service_price||visit.total_amount||30000); if(!svcPrice||svcPrice<=0) svcPrice=30000;
 this.posCart=[{id:null,name:svcName+(visit.ticket_code?' ['+visit.ticket_code+']':''),sku:visit.ticket_code||'VISIT-'+visit.id,price:svcPrice,qty:Number(visit.service_quantity||1),is_service:true}];
 this.posLinkedTicketId=null; this.posNotes=visit.handover_note||visit.customer_statement||''; this.showPosModal=true; this.updateTendered();
 },
 async acceptSalesService(visit){
 if(!visit.service_id){ this.openSalesFeedback('Missing service','No SALES service found for this visit',true); return; }
 try{let res=await fetch('/api/live/services/'+visit.service_id+'/accept',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''}});let data=await res.json();if(data.success||res.ok){ this.openSalesFeedback('Accepted','Accepted: '+(data.message||'Task in progress'),false); this.fetchLiveStats(); }else{ this.openSalesFeedback('Accept failed', data.message||'Accept failed', true); }}catch(e){ this.openSalesFeedback('Accept error','Accept error: '+e.message, true); }
 },
 async completeSalesServicePrompt(visit){ this.openCompleteModal(visit); },
 removeFromCart(idx){this.posCart.splice(idx,1);this.updateTendered();},
 clearCart(){this.posCart=[];this.posCustomerName='Walk-in Customer';this.posCustomerPhone='';this.posCompany='';this.posLinkedTicketId=null;this.posDiscountPct=0;this.posPayRef='';this.posTendered=0;},
 cartSubtotal(){return this.posCart.reduce((s,i)=>s+(Number(i.price||0)*Number(i.qty||0)),0);},
 cartDiscount(){return (this.cartSubtotal()*Number(this.posDiscountPct||0))/100;},
 cartTax(){if(!this.posApplyTax) return 0; return (Math.max(0,this.cartSubtotal()-this.cartDiscount())*0.18);},
 cartTotal(){return Math.max(0,this.cartSubtotal()-this.cartDiscount())+this.cartTax();},
 updateTendered(){this.posTendered=this.cartTotal();},
 changeDue(){return Math.max(0,Number(this.posTendered||0)-this.cartTotal());},
 openReceiptWindow(id){let url='/sales/invoices/'+id+'/print';window.open(url,'invoice_window','width=980,height=780,toolbar=0,menubar=0,location=0');}
}">
<div style="background:#ffffff; border-bottom:1px solid #e0e0e0; margin: -1.5rem -1.5rem 0 -1.5rem; padding: 1rem 1.5rem 0 1.5rem;">
  <div class="ibm-grid" style="padding:0;">
  <nav class="cds--breadcrumb" aria-label="Breadcrumb" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; font-size:0.75rem; color:#525252;">
  <div class="cds--breadcrumb-item"><a class="cds--link" href="/sales" style="color:#0f62fe; text-decoration:none;">Sales</a></div>
  <div class="cds--breadcrumb-item"><span style="color:#8d8d8d;">/</span> <a class="cds--link" href="/sales?tab=dashboard" style="color:#0f62fe; text-decoration:none;" x-show="activeTab==='dashboard'">Revenue Operations</a><a class="cds--link" href="/sales?tab=register" style="color:#0f62fe; text-decoration:none;" x-show="activeTab==='register'" x-cloak>POS</a></div>
  <div class="cds--breadcrumb-item cds--breadcrumb-item--current"><span style="color:#8d8d8d;">/</span> <span style="color:#161616; margin-left:0.5rem;" x-show="activeTab==='dashboard'">Dashboard</span><span style="color:#161616; margin-left:0.5rem;" x-show="activeTab==='register'" x-cloak>Terminal</span></div>
  </nav>
   <div x-show="activeTab==='dashboard'" style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1.25rem; padding-bottom:1.25rem;">
   <div style="flex:1; min-width:280px;">
   <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans', sans-serif; letter-spacing:-0.02em;">Sales dashboard</h1>
   <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:36rem; line-height:1.5; font-family:'IBM Plex Sans', sans-serif; font-weight:400;">Revenue command center — real-time sales, liquidity and showroom handoffs</p>
   </div>
   <div style="display:flex; gap:0.625rem; flex-wrap:wrap; align-items:center; align-self:center;">
   <button @click="showPosModal=true" class="cds--btn cds--btn--primary" type="button" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; box-shadow:0 1px 2px rgba(0,0,0,0.1);">
   <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M17 15V8h-2v7H8v2h7v7h2v-7h7v-2z"/></svg> New POS sale
   </button>
   <a href="{{ route('sales.inventory') }}" class="cds--btn cds--btn--secondary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#393939; color:#fff; border:1px solid #393939; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
   <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M17 15V8h-2v7H8v2h7v7h2v-7h7v-2z"/></svg> Add product
   </a>
   <a href="{{ route('sales.invoices.export') }}" class="cds--btn cds--btn--tertiary" style="height:2.5rem; padding:0 1.25rem; font-weight:600; font-size:0.875rem; background:#ffffff; color:#161616; border:1px solid #8d8d8d; display:inline-flex; align-items:center; gap:0.5rem; text-decoration:none;">
   <svg width="16" height="16" viewBox="0 0 32 32" fill="#525252"><path d="M26 24v4H6v-4H4v4a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2v-4zm0-10l-1.41-1.41L17 20.17V2h-2v18.17l-7.59-7.58L6 14l10 10l10-10z"/></svg> Export CSV
   </a>
   </div>
   </div>
   <div x-show="activeTab==='register'" x-cloak style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1.25rem; padding-bottom:1.25rem;">
   <div style="flex:1; min-width:280px;">
   <h1 style="font-size:2rem; font-weight:400; color:#161616; line-height:1.25; margin:0; font-family:'IBM Plex Sans', sans-serif; letter-spacing:-0.02em;">POS Terminal</h1>
   <p style="font-size:0.875rem; color:#525252; margin:0.375rem 0 0 0; max-width:36rem; line-height:1.5; font-family:'IBM Plex Sans', sans-serif; font-weight:400;">Professional point of sale — click products to add to cart, collect IT bills</p>
   </div>
   <div style="display:flex; gap:0.625rem; flex-wrap:wrap; align-items:center; align-self:center;">
   <span style="background:#e0e0e0; color:#525252; font-size:0.75rem; padding:0.375rem 0.75rem; font-family:'IBM Plex Mono',monospace;" x-text="posCart.length + ' items • TZS ' + cartTotal().toLocaleString()">0 items • TZS 0</span>
   <button @click="clearCart()" class="cds--btn cds--btn--ghost" style="height:2.5rem; padding:0 1rem; font-weight:600; font-size:0.875rem; background:#fff; color:#525252; border:1px solid #e0e0e0;">Clear cart</button>
   <a href="{{ route('sales.history') }}" class="cds--btn cds--btn--tertiary" style="height:2.5rem; padding:0 1rem; font-weight:600; font-size:0.875rem; background:#fff; color:#161616; border:1px solid #8d8d8d; display:inline-flex; align-items:center; gap:0.375rem; text-decoration:none;">History</a>
   </div>
   </div>
 <div class="cds--tabs cds--tabs--container" role="tablist" aria-label="Sales navigation" style="border-bottom:1px solid #e0e0e0; margin:0 -1.5rem; padding:0 1.5rem; background:#fff;">
 <ul class="cds--tabs__nav" role="tablist" style="display:flex; gap:0; overflow-x:auto; margin:0; padding:0; list-style:none;">
 <li class="cds--tabs__nav-item" :class="{'cds--tabs__nav-item--selected': activeTab==='dashboard'}" @click="activeTab='dashboard'" role="tab" :aria-selected="activeTab==='dashboard'" style="display:inline-flex; align-items:center; gap:0.5rem; height:2.5rem; padding:0 1rem; border-bottom:2px solid transparent; cursor:pointer; font-size:0.875rem; color:#525252; white-space:nowrap;" :style="activeTab==='dashboard' ? 'border-bottom-color:#0f62fe; color:#161616; font-weight:600; background:#f4f4f4;' : ''">
 <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M13 13H4V4h9v9zm0 14H4v-9h9v9zm14 0h-9v-9h9v9zm0-14h-9V4h9v9z"/></svg> Dashboard
 </li>
 </ul>
 </div>
 </div>
</div>

<div class="ibm-grid" style="padding-top:1.5rem;">




 <div style="font-size:0.875rem; font-weight:600; color:#161616; margin-top:0.125rem;"></div>
 </div>

<div x-show="activeTab==='dashboard'">

{{-- Tiles — HORIZONTAL GRID Standard — 4-col Grid Design --}}
<div class="ibm-hgrid-4">
 <div style="display:contents;">
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0; padding:1.5rem;">
 <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70; font-weight:500; margin-top:0.5rem;">Mauzo ya Leo / Today</div>
 <div style="font-size:0.6875rem; color:#8d8d8d; font-family:'IBM Plex Mono',monospace;">LIVE tile</div>
 <div style="font-size:2rem; font-weight:300; margin-top:0.875rem; font-family:'IBM Plex Mono',monospace; color:#161616; letter-spacing:-0.03em;" x-text="'TZS ' + formatCurrency(metrics.todaySalesTotal)">TZS {{ number_format($todaySalesTotal) }}</div>
 <div style="display:flex; gap:0.375rem; margin-top:0.875rem; flex-wrap:wrap;">
 <span class="cds--tag" :class="metrics.growthPct>=0 ? 'cds--tag--green' : 'cds--tag--red'" x-text="(metrics.growthPct>=0? '▲ +' : '▼ ') + metrics.growthPct + '% vs Jana'" style="font-family:'IBM Plex Mono',monospace;">▲ +0% vs Jana</span>
 <span class="cds--tag cds--tag--gray" style="font-family:'IBM Plex Mono',monospace;" x-text="metrics.todayTransactionsCount + ' sales'">{{ $todayTransactionsCount }} sales</span>
 </div>
 <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px solid #e0e0e0; display:flex; justify-content:space-between; font-size:0.75rem; color:#525252;">
 <span style="font-family:'IBM Plex Mono',monospace; font-weight:600;" x-text="'Avg TZS ' + formatCurrency(metrics.avgReceiptValue)">Avg TZS {{ number_format($avgReceiptValue) }}</span>
 </div>
 <div style="height:0.25rem; background:#f0f0f0; margin-top:0.75rem;"><div style="height:100%; background:#0f62fe; transition:width 500ms;" :style="'width:' + Math.min(100, metrics.todayTransactionsCount ? 100 : 8) + '%'"></div></div>
 </div>
 <div style="display:contents;">
 <div class="cds--tile" style="background:#ffffff; border:1px solid #e0e0e0; padding:1.5rem;">
 <div style="font-size:0.75rem; font-weight:500; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70;">Mauzo ya Wiki / 7-Day</div>
 <div style="font-size:2rem; font-weight:300; margin-top:0.875rem; font-family:'IBM Plex Mono',monospace; color:#161616; letter-spacing:-0.03em;" x-text="'TZS ' + formatCurrency(metrics.thisWeekSalesTotal)">TZS {{ number_format($thisWeekSalesTotal) }}</div>
 <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px solid #e0e0e0; display:flex; justify-content:space-between; font-size:0.75rem; color:#6a6d70;">
 <span>Daily average</span>
 <span style="font-family:'IBM Plex Mono',monospace; color:#161616; font-weight:600;" x-text="'TZS ' + formatCurrency(Math.round(metrics.thisWeekSalesTotal/7))">TZS {{ number_format((int)round($thisWeekSalesTotal/7)) }}</span>
 </div>
 </div>
 </div>
 <div style="display:contents;">
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0; padding:1.5rem;">
 <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70; font-weight:500; margin-top:0.5rem;">Mwezi Huu / Target</div>
 <div style="font-size:2rem; font-weight:300; margin-top:0.875rem; font-family:'IBM Plex Mono',monospace; color:#161616; letter-spacing:-0.03em;" x-text="'TZS ' + formatCurrency(metrics.thisMonthSalesTotal)">TZS {{ number_format($thisMonthSalesTotal) }}</div>
 <div style="margin-top:0.875rem; height:0.25rem; background:#f0f0f0; border:1px solid #e0e0e0;"><div style="height:100%; background:#0f62fe; transition:width 500ms;" :style="'width:' + Math.min(100, metrics.targetPct) + '%'"></div></div>
 <div style="display:flex; justify-content:space-between; font-size:0.6875rem; color:#525252; margin-top:0.375rem; font-family:'IBM Plex Mono',monospace;"><span>Progress</span><span>Target TZS {{ number_format($monthlyTarget) }}</span></div>
 <div style="margin-top:0.25rem; text-align:right;"><span class="cds--tag cds--tag--teal" x-text="metrics.targetPct + '% Target'" style="font-family:'IBM Plex Mono',monospace;">{{ $targetPct }}% Target</span></div>
 </div>
 </div>
 <div style="display:contents;">
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0; padding:1.5rem;">
 <div style="font-size:0.75rem; font-weight:600; letter-spacing:0.02em; text-transform:uppercase; color:#6a6d70; font-weight:500; margin-top:0.5rem;">Madai / Receivables</div>
 <div style="font-size:2rem; font-weight:300; margin-top:0.875rem; font-family:'IBM Plex Mono',monospace; color:#161616; letter-spacing:-0.03em;" x-text="'TZS ' + formatCurrency(metrics.pendingBillsAmount)">TZS {{ number_format($pendingBillsAmount) }}</div>
 <div style="margin-top:1rem; padding-top:0.75rem; border-top:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#525252;">
 <span x-text="metrics.pendingBillsCount + ' pending'"> pending</span>
 <button @click="activeTab='it_bills'" style="background:#0f62fe; color:#fff; border:0; padding:0.25rem 0.5rem; font-size:0.6875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Review →</button>
 </div>
 </div>
 </div>
 </div>
</div>

<div class="ibm-hgrid-2">
 <div style="display:flex;">
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; width:100%; display:flex; flex-direction:column;">
 <div style="padding:1rem 1.25rem; border-bottom:1px solid #e0e0e0; background:#fff;">
 <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif; color:#161616;">Collections by method</div>
 <div style="font-size:0.75rem; color:#6a6d70; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">Today • Real-time</div>
 </div>
 <div>
 <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f0f0f0; gap:1rem;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <div style="width:0.5rem; height:2.5rem; background:#161616;"></div>
 <div><div style="font-size:0.875rem; font-weight:600; color:#161616;">Cash</div><div style="font-size:0.75rem; color:#6a6d70;">Counter</div></div>
 </div>
 <div style="text-align:right; min-width:9rem;">
 <div style="font-family:'IBM Plex Mono',monospace; font-weight:600; font-size:0.9375rem; color:#161616;" x-text="'TZS ' + formatCurrency(liquidity.todayCashTotal)">TZS {{ number_format($todayCashTotal) }}</div>
 <div style="font-size:0.75rem; color:#6a6d70; margin-top:0.125rem;" x-text="liquidity.cashPct + '%'">({{ $cashPct }}%)</div>
 </div>
 </div>
 <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f0f0f0; gap:1rem;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <div style="width:0.5rem; height:2.5rem; background:#0f62fe;"></div>
 <div><div style="font-size:0.875rem; font-weight:600; color:#161616;">Mobile Money</div><div style="font-size:0.75rem; color:#6a6d70;">M-Pesa</div></div>
 </div>
 <div style="text-align:right; min-width:9rem;">
 <div style="font-family:'IBM Plex Mono',monospace; font-weight:600; font-size:0.9375rem; color:#161616;" x-text="'TZS ' + formatCurrency(liquidity.todayMpesaTotal)">TZS {{ number_format($todayMpesaTotal) }}</div>
 <div style="font-size:0.75rem; color:#6a6d70; margin-top:0.125rem;" x-text="liquidity.mpesaPct + '%'">({{ $mpesaPct }}%)</div>
 </div>
 </div>
 <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f0f0f0; gap:1rem;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <div style="width:0.5rem; height:2.5rem; background:#525252;"></div>
 <div><div style="font-size:0.875rem; font-weight:600; color:#161616;">Card</div><div style="font-size:0.75rem; color:#6a6d70;">POS</div></div>
 </div>
 <div style="text-align:right; min-width:9rem;">
 <div style="font-family:'IBM Plex Mono',monospace; font-weight:600; font-size:0.9375rem; color:#161616;" x-text="'TZS ' + formatCurrency(liquidity.todayCardTotal)">TZS {{ number_format($todayCardTotal) }}</div>
 <div style="font-size:0.75rem; color:#6a6d70; margin-top:0.125rem;" x-text="liquidity.cardPct + '%'">({{ $cardPct }}%)</div>
 </div>
 </div>
 <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; gap:1rem;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <div style="width:0.5rem; height:2.5rem; background:#8d8d8d;"></div>
 <div><div style="font-size:0.875rem; font-weight:600; color:#161616;">Bank</div><div style="font-size:0.75rem; color:#6a6d70;">Transfer</div></div>
 </div>
 <div style="text-align:right; min-width:9rem;">
 <div style="font-family:'IBM Plex Mono',monospace; font-weight:600; font-size:0.9375rem; color:#161616;" x-text="'TZS ' + formatCurrency(liquidity.todayBankTotal)">TZS {{ number_format($todayBankTotal) }}</div>
 <div style="font-size:0.75rem; color:#6a6d70; margin-top:0.125rem;" x-text="liquidity.bankPct + '%'">({{ $bankPct }}%)</div>
 </div>
 </div>
 </div>
 </div>
 </div>
 <div style="display:flex;">
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; width:100%; display:flex; flex-direction:column;">
 <div style="padding:1rem; border-bottom:1px solid #e0e0e0; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0; background:#fff;">
 <div style="font-size:0.875rem; font-weight:600;">Showroom floor queue list — Live handoffs</div>
 <div style="font-size:0.75rem; color:#525252; margin-top:0.125rem;">Reception → Sales — </div>
 </div>
 <div style="flex:1; max-height:24rem; overflow-y:auto;">
 <template x-for="v in receptionVisits" :key="v.id">
 <div style="display:flex; align-items:flex-start; justify-content:space-between; padding:1rem; border-bottom:1px solid #e0e0e0; gap:0.75rem;">
 <div style="flex:1; min-width:0;">
 <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
 </div>
 <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;">
 <span style="color:#8d8d8d;"> • <span x-text="v.arrival_human"></span></span>
 </div>
 <div style="font-size:0.75rem; color:#525252; font-style:italic; margin-top:0.125rem; font-family:'IBM Plex Sans',sans-serif;" x-show="v.handover_note" x-text="'↳ ' + v.handover_note"></div>
 </div>
 <div style="display:flex; gap:0.375rem; flex-shrink:0;">
 <button x-show="v.service_status==='pending'" @click="acceptSalesService(v)" class="cds--btn cds--btn--tertiary cds--btn--sm" style="min-height:1.75rem; font-size:0.75rem;" type="button">Accept</button>
 <button x-show="v.service_status==='in_progress'" @click="completeSalesServicePrompt(v)" class="cds--btn cds--btn--primary cds--btn--sm" style="min-height:1.75rem; font-size:0.75rem;" type="button">Complete</button>
 <button @click="loadVisitToCart(v)" class="cds--btn cds--btn--ghost cds--btn--sm" style="min-height:1.75rem; font-size:0.75rem;" type="button">Bill</button>
 </div>
 </div>
 </template>
 <div x-show="!receptionVisits || receptionVisits.length===0" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
 <div style="width:3rem;height:2rem;background:#0f62fe;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem; font-weight:700; font-size:0.75rem; font-family:'IBM Plex Mono',monospace;"></div>
 No queued customers — waiting for reception handoff.
 </div>
 </div>
 </div>
 </div>
</div>

 {{-- IT Repair Bills — Collect on Dashboard — Reception → IT → Sales flow --}}
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0;">
   <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between; background:#f4f4f4;">
     <div>
       <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">IT Repair Bills — Ready for Collection</div>
       <div style="font-size:0.75rem; color:#525252; margin-top:0.125rem;">IT resolved • priced • pending sales collection — Click Collect to add to POS cart</div>
     </div>
     <div style="display:flex; align-items:center; gap:0.5rem;">
       <span class="cds--tag cds--tag--gray" style="font-family:'IBM Plex Mono',monospace;" x-text="unpaidTickets.length + ' pending • TZS ' + formatCurrency(unpaidTickets.reduce((s,t)=>s+Number(t.price||0),0))">{{ $unpaidItTickets->count() }} pending</span>
       <span class="cds--tag cds--tag--teal" style="font-family:'IBM Plex Mono',monospace;">Live</span>
     </div>
   </div>
   <div style="max-height:18rem; overflow-y:auto;">
     <template x-for="ticket in unpaidTickets.slice(0,5)" :key="ticket.id">
       <div style="display:flex; align-items:center; justify-content:space-between; padding:0.875rem 1rem; border-bottom:1px solid #f0f0f0; gap:0.75rem;">
         <div style="flex:1; min-width:0;">
           <div style="display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap;">
             <span style="font-size:0.8125rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="ticket.title"></span>
             <span style="font-family:'IBM Plex Mono',monospace; font-size:0.6875rem; background:#e0e0e0; color:#393939; padding:0.125rem 0.375rem;" x-text="ticket.ticket_code || ('TKT-'+ticket.id)"></span>
             <span style="font-size:0.6875rem; padding:0.125rem 0.375rem; background:#fff8e1; border:1px solid #f1c21b; color:#684e00; font-weight:600;" x-text="ticket.category"></span>
           </div>
           <div style="font-size:0.75rem; color:#525252; margin-top:0.25rem;">
             <span x-text="ticket.visitor_name || 'Walk-in'"></span> • <span x-text="ticket.visitor_phone || '—'"></span>
           </div>
         </div>
         <div style="display:flex; align-items:center; gap:0.75rem; flex-shrink:0;">
           <div style="text-align:right;">
             <div style="font-family:'IBM Plex Mono',monospace; font-size:0.9375rem; font-weight:700; color:#161616;" x-text="'TZS ' + formatCurrency(ticket.price || 50000)">TZS 0</div>
             <div style="font-size:0.6875rem; color:#8d8d8d;">IT priced</div>
           </div>
           <button @click="loadItTicketToCart(ticket)" style="height:2rem; padding:0 0.75rem; background:#0f62fe; color:#fff; border:1px solid #0f62fe; font-size:0.75rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Collect</button>
         </div>
       </div>
     </template>
     <div x-show="unpaidTickets.length===0" style="padding:1.5rem; text-align:center; color:#525252; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">No IT bills pending — all collected</div>
     <div x-show="unpaidTickets.length>5" style="padding:0.5rem 1rem; text-align:center; border-top:1px solid #e0e0e0; background:#f4f4f4;">
       <a @click="activeTab='it_bills'" style="font-size:0.75rem; color:#0f62fe; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">View all <span x-text="unpaidTickets.length"></span> pending →</a>
     </div>
   </div>
   <div style="padding:0.5rem 1rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:space-between; font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">
     <span>Reception → IT → Sales flow: IT sets price → Sales collects → IT sees Paid</span>
     <span style="color:#8d8d8d;">Live every 8s</span>
   </div>
 </div>

 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; margin-bottom:1rem; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0;">
  <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; background:#fff;">
  <div>
  <div style="font-size:0.875rem; font-weight:600;">7-day sales velocity & fiscal trend chart</div>
 <div style="font-size:0.75rem; color:#525252; margin-top:0.125rem;"><span style="font-family:'IBM Plex Mono',monospace; background:#e0e0e0; padding:0.125rem 0.25rem;"></span></div>
 </div>
 <span class="cds--tag cds--tag--gray" style="font-family:'IBM Plex Mono',monospace;">7-day sum TZS <span x-text="formatCurrency(metrics.thisWeekSalesTotal)">{{ number_format($thisWeekSalesTotal) }}</span></span>
 </div>
 <div style="padding:1rem;">
 <div style="height:14rem; position:relative; background:#f4f4f4; border:1px solid #e0e0e0; padding:0.5rem;">
 <canvas id="weeklySalesChart"></canvas>
 </div>
  <div style="display:flex; gap:1rem; margin-top:0.75rem; font-size:0.75rem; color:#6a6d70; font-family:'IBM Plex Sans',sans-serif;">
  <span style="display:flex; align-items:center; gap:0.375rem;"><span style="width:0.75rem;height:0.75rem;background:#0f62fe;display:inline-block;"></span> Daily sales</span>
  </div>
 </div>
</div>

<div class="cds--data-table-container" style="background:#fff; border:1px solid #e0e0e0; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0;">
 <div style="padding:1rem; background:#fff; border-bottom:1px solid #e0e0e0;">
 <div style="display:flex; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
 <div>
 <div style="font-size:1rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Recent receipts</div>
 </div>
 <span class="cds--tag cds--tag--gray" style="font-family:'IBM Plex Mono',monospace;">{{ $invoices->count() }} items</span>
 </div>
 </div>
 <div class="cds--table-toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; min-height:3rem; padding:0 1rem; background:#f4f4f4; border-top:1px solid #e0e0e0; border-bottom:1px solid #e0e0e0;">
 <div x-show="selectedRows.length>0" style="display:flex; align-items:center; justify-content:space-between; width:100%; background:#0f62fe; color:#fff; margin:-0.5rem -1rem; padding:0.5rem 1rem;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <strong style="font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"><span x-text="selectedRows.length"></span> selected</strong>
 <button @click="selectedRows=[];selectAllRows=false;" style="font-size:0.75rem; color:#fff; text-decoration:underline; background:transparent; border:0; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
 </div>
  <button type="button" @click="openSalesFeedback('Export','Exporting ' + selectedRows.length + ' receipts…')" class="cds--btn cds--btn--sm" style="background:#0353e9; color:#fff; border-color:#0353e9; font-family:'IBM Plex Sans',sans-serif;">Export selected</button>
 </div>
 <div x-show="selectedRows.length===0" style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:0.75rem; flex-wrap:wrap;">
 <div style="display:flex; align-items:center; gap:0.75rem; flex:1; flex-wrap:wrap;">
 <div class="cds--search cds--search--sm" style="position:relative; display:flex; align-items:center; width:19rem;">
 <svg style="position:absolute; left:0.5rem; width:1rem; height:1rem; fill:#525252;" viewBox="0 0 32 32"><path d="M14 4A10 10 0 1 0 24 14A10 10 0 0 0 14 4zm0 18A8 8 0 1 1 22 14A8 8 0 0 1 14 22z"/><path d="M26.7 24.7L21.3 19.3L20 20.7l5.4 5.4z"/></svg>
 <input class="cds--search-input" type="text" placeholder="Search" x-model="tableSearch" aria-label="Search table" style="width:100%; height:2rem; background:#fff; border:0; border-bottom:1px solid #8d8d8d; padding:0 0.75rem 0 2rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
 <button x-show="tableSearch" @click="tableSearch=''" style="position:absolute; right:0.375rem; background:transparent; border:0; cursor:pointer; color:#525252;">×</button>
 </div>
 <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.75rem; color:#525252;">
 <span style="font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Status</span>
 <select x-model="tableFilterStatus" class="cds--select-input" style="height:2rem; padding:0 0.5rem; background:#fff; border:0; border-bottom:1px solid #8d8d8d; font-size:0.75rem; font-family:'IBM Plex Mono',monospace;">
 <option value="all">All</option>
 <option value="paid">Settled (Paid)</option>
 <option value="pending_payment">Pending</option>
 </select>
 </div>
 </div>
 <div style="display:flex; gap:0.5rem;">
 <button @click="activeTab='register'" class="cds--btn cds--btn--primary cds--btn--sm" style="font-family:'IBM Plex Sans',sans-serif;">New register sale</button>
 <a href="{{ route('sales.invoices.export') }}" class="cds--btn cds--btn--tertiary cds--btn--sm" style="font-family:'IBM Plex Sans',sans-serif;">Export CSV</a>
 </div>
 </div>
 </div>
 <div style="overflow-x:auto;">
 <table class="cds--data-table" style="width:100%; border-collapse:collapse; font-size:0.875rem;">
 <thead>
 <tr>
 <th style="width:2.75rem; text-align:center; background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;"><input type="checkbox" @change="toggleSelectAll({{ json_encode($invoices->take(10)->toArray()) }})" x-model="selectAllRows" style="accent-color:#0f62fe;"></th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; font-family:'IBM Plex Sans',sans-serif;">Receipt #</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Date & time</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Customer / organization</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Service & items</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6;">Payment channel</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; text-align:right;">Amount (TZS)</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; text-align:center;">Status</th>
 <th style="background:#e0e0e0; padding:0.75rem 1rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.02em; border-bottom:1px solid #c6c6c6; text-align:right;">Actions</th>
 </tr>
 </thead>
 <tbody>
 @forelse($invoices->take(10) as $inv)
 @php
 $payMethod = strtoupper($inv->payment_method ?? 'CASH');
 $methodClass = match($payMethod){
 'M-PESA','MPESA','AIRTEL MONEY','TIGO PESA' => 'cds--tag--green',
 'CARD','VISA','MASTERCARD' => 'cds--tag--blue',
 'BANK','BANK TRANSFER' => 'cds--tag--purple',
 default => 'cds--tag--gray',
 };
 @endphp
 <tr x-show="(tableFilterStatus==='all' || '{{ $inv->status }}'===tableFilterStatus) && (!tableSearch || '{{ strtolower($inv->customer_name.' '.($inv->receipt_number??'').' '.$payMethod.' '.$inv->service) }}'.includes(tableSearch.toLowerCase()))" :class="{'cds--data-table--selected': selectedRows.includes({{ $inv->id }})}" style="{{ $loop->even ? 'background:#f4f4f4;' : 'background:#fff;' }} border-bottom:1px solid #e0e0e0;">
 <td style="text-align:center; padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0;"><input type="checkbox" value="{{ $inv->id }}" x-model="selectedRows" style="accent-color:#0f62fe;"></td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; font-family:'IBM Plex Mono',monospace; font-weight:600; color:#161616;">{{ $inv->receipt_number ?? ('#ORD-'.$inv->id) }}</td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; font-size:0.75rem; color:#525252;">{{ $inv->paid_at ? $inv->paid_at->format('d M Y, h:i A') : ($inv->created_at ? $inv->created_at->format('d M Y, h:i A') : '—') }}</td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0;">
 <div style="font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">{{ $inv->customer_name ?: 'Walk-in Customer' }}</div>
 @if($inv->company)<div style="font-size:0.75rem; color:#525252;">{{ $inv->company }}</div>@endif
 </td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; max-width:14rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.8125rem; color:#525252;">{{ $inv->service ?: 'Showroom retail sale' }}</td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0;"><span class="cds--tag {{ $methodClass }}" style="font-family:'IBM Plex Mono',monospace; font-weight:600;">{{ $payMethod }}</span></td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; text-align:right; font-family:'IBM Plex Mono',monospace; font-weight:700;">TZS {{ number_format($inv->amount) }}</td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; text-align:center;"><span class="cds--tag {{ $inv->status==='paid' ? 'cds--tag--green' : 'cds--tag--gray' }}">{{ $inv->status==='paid' ? 'Settled' : 'Pending' }}</span></td>
 <td style="padding:0.75rem 1rem; border-bottom:1px solid #e0e0e0; text-align:right;"><button @click="openReceiptWindow({{ $inv->id }})" class="cds--btn cds--btn--ghost cds--btn--sm" style="min-height:1.75rem; font-size:0.75rem;" type="button">Print</button></td>
 </tr>
 @empty
 <tr><td colspan="9" style="padding:2rem; text-align:center; font-size:0.875rem; color:#525252;">No receipts yet</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="cds--pagination" style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1rem; background:#fff; border-top:1px solid #e0e0e0; font-size:0.75rem; color:#525252;">
 <div style="display:flex; gap:0.75rem; align-items:center;">
 <span>Items per page <strong style="font-family:'IBM Plex Mono',monospace; color:#161616;">10</strong></span>
 <span style="color:#c6c6c6;">|</span>
 <span>Showing 1–{{ min(10,$invoices->count()) }} of {{ $invoices->count() }} items</span>
 </div>
 </div>
</div>

</div>

{{-- OTHER TABS — KEEP MINIMAL BUT PURE --}}
<div x-show="activeTab==='it_bills'" x-cloak>
 <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0; padding:0;">
 <div style="padding:1rem; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center;">
 <div>
 <div style="font-size:0.875rem; font-weight:600;">Repair bills ready for collection — </div>
 <div style="font-size:0.75rem; color:#525252; margin-top:0.125rem;">IT Service Desk structured list</div>
 </div>
 <span class="cds--tag cds--tag--gray" style="font-family:'IBM Plex Mono',monospace;" x-text="(unpaidTickets ? unpaidTickets.length : {{ $unpaidItTickets->count() }}) + ' pending'">{{ $unpaidItTickets->count() }} pending</span>
 </div>
 <div>
 <template x-for="ticket in unpaidTickets" :key="ticket.id">
 <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem; border-bottom:1px solid #e0e0e0; gap:0.75rem;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <div style="width:2.5rem; height:2.5rem; background:#0f62fe; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.6875rem; font-family:'IBM Plex Mono',monospace;"></div>
 <div>
 <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
 </div>
 <div style="font-size:0.75rem; color:#525252;">Guest <strong style="color:#161616;" x-text="ticket.visitor_name||'Walk-in'"></strong> • <span x-text="ticket.visitor_phone||'—'"></span> • <span style="font-family:'IBM Plex Mono',monospace;" x-text="'TZS ' + formatCurrency(ticket.price||50000)"></span></div>
 </div>
 </div>
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <div style="text-align:right;">
 <div style="font-family:'IBM Plex Mono',monospace; font-weight:700;" x-text="'TZS ' + formatCurrency(ticket.price||50000)"></div>
 <div style="font-size:0.625rem; color:#525252; text-transform:uppercase; letter-spacing:0.02em;">Ready</div>
 </div>
 <button @click="loadItTicketToCart(ticket)" class="cds--btn cds--btn--primary cds--btn--sm">Collect</button>
 </div>
 </div>
 </template>
 <div x-show="!unpaidTickets || unpaidTickets.length===0" style="padding:2rem; text-align:center; color:#525252; font-size:0.875rem;">All tickets settled</div>
 </div>
 </div>
</div>


<div x-show="activeTab==='register'" x-cloak>
  {{-- Real POS Terminal — Professional — Click product to add --}}
  <div style="display:grid; grid-template-columns: 1.6fr 0.9fr; gap:1rem; align-items:start;">
    {{-- Left: Product Catalog --}}
    <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; display:flex; flex-direction:column; min-height:32rem;">

      <div style="padding:1rem; border-bottom:1px solid #e0e0e0; background:#fff;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
          <div style="flex:1; position:relative; min-width:200px;">
            <svg style="position:absolute; left:0.625rem; top:50%; transform:translateY(-50%); width:1rem; height:1rem; fill:#525252;" viewBox="0 0 32 32"><path d="M14 4A10 10 0 1 0 24 14A10 10 0 0 0 14 4zm0 18A8 8 0 1 1 22 14A8 8 0 0 1 14 22z"/><path d="M26.7 24.7L21.3 19.3L20 20.7l5.4 5.4z"/></svg>
            <input type="text" x-model="searchQuery" placeholder="Search products, SKU..." style="width:100%; height:2.5rem; background:#f4f4f4; border:1px solid #e0e0e0; border-bottom:1px solid #8d8d8d; padding:0 0.75rem 0 2rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
          </div>
          <select x-model="selectedCategory" style="height:2.5rem; padding:0 0.75rem; background:#fff; border:1px solid #e0e0e0; border-bottom:1px solid #8d8d8d; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif; min-width:140px;">
            <option value="All">All categories</option>
            @foreach($categories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
          </select>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.75rem; flex-wrap:wrap;">
          <span style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Click any product to add</span>
          <span style="font-size:0.75rem; color:#0f62fe; font-weight:600; font-family:'IBM Plex Sans',sans-serif;" x-text="'• ' + posCart.length + ' in cart'">• 0 in cart</span>
        </div>
      </div>
      <div style="flex:1; padding:1rem; background:#f4f4f4; overflow-y:auto;">
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:0.75rem; align-content:start;">
          @foreach($products as $p)
          <div @click="if(@js($p->is_service) || @js($p->stock_quantity ?? 0) > 0) addToCart(@js($p))"
               x-show="(selectedCategory === 'All' || selectedCategory === '{{ $p->category }}') && ('{{ strtolower($p->name) }} {{ strtolower($p->sku) }}'.includes(searchQuery.toLowerCase()))"
               :style="@js(!$p->is_service && ($p->stock_quantity ?? 0) == 0) ? 'background:#f4f4f4; border:1px solid #e0e0e0; padding:0.875rem; opacity:0.5; cursor:not-allowed; display:flex; flex-direction:column; justify-content:space-between; min-height:7rem;' : 'background:#fff; border:1px solid #e0e0e0; padding:0.875rem; cursor:pointer; display:flex; flex-direction:column; justify-content:space-between; min-height:7rem; transition: all 120ms; position:relative;'"
               onmouseover="if(this.style.cursor!=='not-allowed'){this.style.borderColor='#0f62fe'; this.style.boxShadow='0 2px 6px rgba(15,98,254,0.15)'}" onmouseout="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
            <div>
              <div style="display:flex; align-items:center; gap:0.375rem; margin-bottom:0.375rem;">
                <span style="width:1.5rem; height:1.5rem; background:#f4f4f4; border:1px solid #e0e0e0; display:flex; align-items:center; justify-content:center; font-size:0.625rem; color:#525252;">
                  <span class="material-symbols-outlined" style="font-size:14px;">{{ $p->icon ?? 'inventory_2' }}</span>
                </span>
                <span style="font-size:0.625rem; color:#6a6d70; font-family:'IBM Plex Mono',monospace; text-transform:uppercase; letter-spacing:0.02em;">{{ $p->category }}</span>
              </div>
              <div style="font-size:0.8125rem; font-weight:600; color:#161616; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.1rem; font-family:'IBM Plex Sans',sans-serif;">{{ $p->name }}</div>
              <div style="font-size:0.6875rem; color:#8d8d8d; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;">{{ $p->sku }} @if(!$p->is_service) • <span style="color:{{ ($p->stock_quantity ?? 0) == 0 ? '#da1e28' : (($p->stock_quantity ?? 0) <= 5 ? '#e9730c' : '#24a148') }}; font-weight:600;">{{ $p->stock_quantity ?? 0 }} in stock</span> @else • <span style="color:#0f62fe;">Service</span> @endif</div>
              @if(!$p->is_service && ($p->stock_quantity ?? 0) <= 5)
              <div style="margin-top:0.375rem; font-size:0.625rem; color:#da1e28; font-weight:600;">Low stock: {{ $p->stock_quantity ?? 0 }}</div>
              @endif
            </div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:0.75rem;">
              <div style="font-size:0.9375rem; font-weight:700; font-family:'IBM Plex Mono',monospace; color:#161616;">TZS {{ number_format($p->price) }}</div>
              <span style="width:1.75rem; height:1.75rem; background:#0f62fe; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1rem; line-height:1;">+</span>
            </div>
          </div>
          @endforeach
        </div>
        <button type="button" @click="showCustomItemModal=true" style="width:100%; margin-top:0.75rem; height:2.5rem; border:1px dashed #8d8d8d; background:#fff; color:#525252; font-size:0.8125rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem; font-family:'IBM Plex Sans',sans-serif;">
          <svg width="16" height="16" viewBox="0 0 32 32" fill="currentColor"><path d="M17 15V8h-2v7H8v2h7v7h2v-7h7v-2z"/></svg> Add custom item
        </button>
      </div>
    </div>

    {{-- Right: Cart / Tender --}}
    <form action="{{ route('sales.checkout') }}" method="POST" style="position:sticky; top:1rem;">
      @csrf
      <input type="hidden" name="it_ticket_id" :value="posLinkedTicketId">
      <div class="cds--tile" style="background:#fff; border:1px solid #e0e0e0; padding:0; display:flex; flex-direction:column;">
        <div style="padding:1rem; background:#161616; color:#fff;">
          <div style="display:flex; align-items:center; justify-content:space-between;">
            <div style="font-size:0.875rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Cart</div>
            <span style="background:#393939; color:#fff; font-size:0.75rem; padding:0.125rem 0.5rem; font-family:'IBM Plex Mono',monospace;" x-text="posCart.length + ' items'">0 items</span>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-top:0.75rem;">
            <input type="text" name="customer_name" x-model="posCustomerName" placeholder="Customer name" style="height:2rem; padding:0 0.5rem; background:#262626; border:1px solid #393939; border-bottom:1px solid #8d8d8d; color:#fff; font-size:0.8125rem; font-family:'IBM Plex Sans',sans-serif;">
            <input type="text" name="customer_phone" x-model="posCustomerPhone" placeholder="Phone +255..." style="height:2rem; padding:0 0.5rem; background:#262626; border:1px solid #393939; border-bottom:1px solid #8d8d8d; color:#fff; font-size:0.8125rem; font-family:'IBM Plex Sans',sans-serif;">
          </div>
        </div>

        <div style="flex:1; background:#fff; min-height:18rem; max-height:22rem; overflow-y:auto; border-bottom:1px solid #e0e0e0;">
          <template x-for="(item, idx) in posCart" :key="idx">
            <div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; border-bottom:1px solid #f4f4f4;">
              <div style="flex:1; min-width:0;">
                <div style="font-size:0.8125rem; font-weight:600; color:#161616; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;" x-text="item.name"></div>
                <div style="font-size:0.75rem; color:#525252; font-family:'IBM Plex Mono',monospace;" x-text="'TZS ' + Number(item.price).toLocaleString() + ' × ' + item.qty"></div>
                <input type="hidden" :name="'items[' + idx + '][name]'" :value="item.name">
                <input type="hidden" :name="'items[' + idx + '][price]'" :value="item.price">
                <input type="hidden" :name="'items[' + idx + '][qty]'" :value="item.qty">
                <input type="hidden" :name="'items[' + idx + '][sku]'" :value="item.sku">
                <input type="hidden" :name="'items[' + idx + '][id]'" :value="item.id">
              </div>
              <div style="display:flex; align-items:center; gap:0.375rem;">
                <button type="button" @click="item.qty>1 ? item.qty-- : removeFromCart(idx); updateTendered()" style="width:1.75rem; height:1.75rem; background:#f4f4f4; border:1px solid #e0e0e0; cursor:pointer; font-weight:600;">−</button>
                <span style="min-width:1.5rem; text-align:center; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Mono',monospace;" x-text="item.qty"></span>
                <button type="button" @click="if(item.stock_quantity !== null && !item.is_service && Number(item.qty) >= Number(item.stock_quantity)){ openStockAlert('No more stock for ' + item.name + ': only ' + item.stock_quantity + ' available'); } else { item.qty++; updateTendered(); }" style="width:1.75rem; height:1.75rem; background:#f4f4f4; border:1px solid #e0e0e0; cursor:pointer; font-weight:600;">+</button>
                <button type="button" @click="removeFromCart(idx)" style="margin-left:0.25rem; background:none; border:0; color:#da1e28; cursor:pointer; font-size:1.125rem;">×</button>
              </div>
              <div style="min-width:5rem; text-align:right; font-size:0.8125rem; font-weight:700; font-family:'IBM Plex Mono',monospace;" x-text="'TZS ' + (Number(item.price)*Number(item.qty)).toLocaleString()"></div>
            </div>
          </template>
          <div x-show="posCart.length===0" style="padding:2.5rem 1rem; text-align:center;">
            <div style="width:3rem; height:3rem; background:#f4f4f4; border:1px solid #e0e0e0; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem; color:#8d8d8d;">
              <span class="material-symbols-outlined">shopping_cart</span>
            </div>
            <div style="font-size:0.875rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Cart is empty</div>
            <div style="font-size:0.75rem; color:#6a6d70; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;">Click any product on the left to add</div>
          </div>
        </div>

        <div style="padding:1rem; background:#f4f4f4; border-top:1px solid #e0e0e0;">
          <div style="font-size:0.75rem; color:#525252; display:flex; justify-content:space-between; font-family:'IBM Plex Sans',sans-serif;"><span>Subtotal</span><span style="font-family:'IBM Plex Mono',monospace;" x-text="'TZS ' + cartSubtotal().toLocaleString()">TZS 0</span></div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid #e0e0e0;">
            <span style="font-size:0.875rem; font-weight:700; font-family:'IBM Plex Sans',sans-serif;">Total</span>
            <span style="font-size:1.25rem; font-weight:700; font-family:'IBM Plex Mono',monospace; color:#161616;" x-text="'TZS ' + cartTotal().toLocaleString()">TZS 0</span>
          </div>
          <input type="hidden" name="subtotal" :value="cartSubtotal()">
          <input type="hidden" name="discount_amount" :value="cartDiscount()">
          <input type="hidden" name="tax_amount" :value="cartTax()">
          <input type="hidden" name="total_amount" :value="cartTotal()">

          <div style="margin-top:1rem;">
            <div style="font-size:0.75rem; font-weight:600; color:#525252; margin-bottom:0.5rem; font-family:'IBM Plex Sans',sans-serif;">Payment method</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
              <label style="cursor:pointer;"><input type="radio" name="payment_method" value="cash" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='cash' ? 'background:#161616; color:#fff; border:2px solid #161616;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.625rem; text-align:center; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Cash</div></label>
              <label style="cursor:pointer;"><input type="radio" name="payment_method" value="mpesa" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='mpesa' ? 'background:#0f62fe; color:#fff; border:2px solid #0f62fe;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.625rem; text-align:center; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">M-Pesa</div></label>
              <label style="cursor:pointer;"><input type="radio" name="payment_method" value="card" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='card' ? 'background:#161616; color:#fff; border:2px solid #161616;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.625rem; text-align:center; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Card</div></label>
              <label style="cursor:pointer;"><input type="radio" name="payment_method" value="bank_transfer" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='bank_transfer' ? 'background:#161616; color:#fff; border:2px solid #161616;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.625rem; text-align:center; font-size:0.8125rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Bank</div></label>
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-top:1rem;">
            <button type="button" @click="clearCart()" style="height:2.5rem; background:#fff; border:1px solid #e0e0e0; font-size:0.8125rem; font-weight:600; color:#525252; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Clear</button>
            <button type="submit" :disabled="posCart.length===0" style="height:2.5rem; font-size:0.875rem; font-weight:700; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; border:1px solid transparent; letter-spacing:0.02em;" :style="posCart.length===0 ? 'background:#c6c6c6 !important; border-color:#c6c6c6 !important; color:#525252 !important; opacity:1; pointer-events:none;' : 'background:#24a148 !important; border-color:#198038 !important; color:#ffffff !important; opacity:1; box-shadow:0 2px 6px rgba(36,161,72,0.3);'">Pay • <span x-text="'TZS ' + cartTotal().toLocaleString()">TZS 0</span></button>
          </div>
          <div style="text-align:center; margin-top:0.5rem; font-size:0.6875rem; color:#8d8d8d; font-family:'IBM Plex Sans',sans-serif;">Click product → auto-add • Adjust qty in cart</div>
        </div>
      </div>
    </form>
  </div>



</div>

<div x-show="showPosModal" x-cloak class="cds--modal is-visible" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(22,22,22,0.5); backdrop-filter:blur(1px);" :style="activeTab==='register' ? 'top:3rem; left:0; right:0; bottom:0; padding:0; background:#f4f4f4; align-items:stretch; ' : ''">
 <div @click.away="activeTab!=='register' && (showPosModal=false)" class="cds--modal-container" :style="activeTab==='register' ? 'width:100%; height:100%; max-width:none; max-height:none; margin:0;' : 'width:100%; max-width:72rem; max-height:92vh;'" style="background:#fff; border:1px solid #e0e0e0; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
 <div class="cds--modal-header" style="background:#161616; color:#fff; padding:0 1rem; height:3rem; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
 <div style="display:flex; align-items:center; gap:0.75rem;">
 <span style="font-weight:600; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">POS terminal modal</span>
 <span class="cds--tag cds--tag--blue" style="height:1.25rem; font-family:'IBM Plex Mono',monospace;">Tender</span>
 </div>
 <button @click="showPosModal=false" style="background:transparent; border:0; color:#fff; cursor:pointer; font-size:1.5rem; padding:0.25rem;">×</button>
 </div>
 <form action="{{ route('sales.checkout') }}" method="POST" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
 @csrf
 <input type="hidden" name="it_ticket_id" :value="posLinkedTicketId">
 <div style="flex:1; display:flex; overflow:hidden; flex-direction:row;">
 <div style="flex:0 0 58%; padding:1rem; display:flex; flex-direction:column; gap:0.75rem; overflow-y:auto; border-right:1px solid #e0e0e0; background:#f4f4f4;">
 <div class="cds--search cds--search--sm" style="position:relative;">
 <svg style="position:absolute; left:0.625rem; top:50%; transform:translateY(-50%); width:1rem; height:1rem; fill:#525252;" viewBox="0 0 32 32"><path d="M14 4A10 10 0 1 0 24 14A10 10 0 0 0 14 4zm0 18A8 8 0 1 1 22 14A8 8 0 0 1 14 22z"/><path d="M26.7 24.7L21.3 19.3L20 20.7l5.4 5.4z"/></svg>
 <input type="text" x-model="searchQuery" placeholder="Search products, SKU, or services search" class="cds--search-input" style="width:100%; height:2.5rem; background:#fff; border:0; border-bottom:1px solid #8d8d8d; padding:0 0.75rem 0 2rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
 </div>
 <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(10rem,1fr)); gap:0.5rem; flex:1; overflow-y:auto; align-content:start; max-height:26rem; padding:0.25rem;">
  @foreach($products as $p)
  <div @click="if(@js($p->is_service) || @js($p->stock_quantity ?? 0) > 0) addToCart(@js($p))" x-show="'{{ strtolower($p->name) }} {{ strtolower($p->sku) }}'.includes(searchQuery.toLowerCase())" :style="@js(!$p->is_service && ($p->stock_quantity ?? 0) == 0) ? 'background:#f4f4f4; border:1px solid #e0e0e0; padding:0.75rem; opacity:0.5; cursor:not-allowed; display:flex; flex-direction:column; justify-content:space-between; min-height:6rem;' : 'background:#fff; border:1px solid #e0e0e0; padding:0.75rem; cursor:pointer; display:flex; flex-direction:column; justify-content:space-between; min-height:6rem;'">
  <div>
  <div style="font-size:0.75rem; font-weight:600; color:#161616; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2rem; font-family:'IBM Plex Sans',sans-serif;">{{ $p->name }}</div>
  <div style="font-size:0.625rem; color:#525252; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;">{{ $p->sku }} @if(!$p->is_service) • <span style="color:{{ ($p->stock_quantity ?? 0) == 0 ? '#da1e28' : (($p->stock_quantity ?? 0) <= 5 ? '#e9730c' : '#24a148') }};">{{ $p->stock_quantity ?? 0 }} in stock</span> @else • <span style="color:#0f62fe;">Service</span> @endif</div>
  </div>
  <div style="font-size:0.8125rem; font-weight:700; font-family:'IBM Plex Mono',monospace; color:#161616; margin-top:0.5rem;">TZS {{ number_format($p->price) }}</div>
  </div>
  @endforeach
 </div>
 <button type="button" @click="showCustomItemModal=true" style="width:100%; height:2.5rem; border:1px dashed #0f62fe; background:#fff; color:#0f62fe; font-size:0.75rem; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem; font-family:'IBM Plex Sans',sans-serif;">
 ＋ Add custom ad-hoc charge dashed — </button>
 </div>
 <div style="flex:1; padding:1rem; display:flex; flex-direction:column; gap:0.75rem; overflow:hidden; background:#fff;">
 <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
 <div><label class="cds--label" style="font-size:0.75rem; font-weight:400; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Customer name</label><input type="text" name="customer_name" x-model="posCustomerName" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 <div><label class="cds--label" style="font-size:0.75rem; font-weight:400; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Phone</label><input type="text" name="customer_phone" x-model="posCustomerPhone" placeholder="+255.." class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; margin-top:0.25rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 </div>
 <div style="flex:1; background:#f4f4f4; border:1px solid #e0e0e0; padding:0.5rem; overflow-y:auto; min-height:11rem;">
 <template x-for="(item, idx) in posCart" :key="idx">
 <div style="padding:0.5rem; display:flex; align-items:center; justify-content:space-between; background:#fff; border-bottom:1px solid #e0e0e0; margin-bottom:0.375rem;">
 <div style="min-width:0; flex:1; padding-right:0.5rem;">
 <div style="font-size:0.8125rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'IBM Plex Sans',sans-serif;" x-text="item.name"></div>
 <div style="font-size:0.625rem; color:#525252; font-family:'IBM Plex Mono',monospace;" x-text="'TZS ' + Number(item.price).toLocaleString() + ' × ' + item.qty"></div>
 <input type="hidden" :name="'items[' + idx + '][name]'" :value="item.name">
 <input type="hidden" :name="'items[' + idx + '][price]'" :value="item.price">
 <input type="hidden" :name="'items[' + idx + '][qty]'" :value="item.qty">
 <input type="hidden" :name="'items[' + idx + '][sku]'" :value="item.sku">
 <input type="hidden" :name="'items[' + idx + '][id]'" :value="item.id">
 </div>
 <div style="display:flex; align-items:center; gap:0.25rem; flex-shrink:0;">
 <button type="button" @click="item.qty>1 ? item.qty-- : removeFromCart(idx); updateTendered()" style="width:1.75rem;height:1.75rem;background:#e0e0e0;border:0;cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">−</button>
 <button type="button" @click="item.qty++; updateTendered()" style="width:1.75rem;height:1.75rem;background:#e0e0e0;border:0;cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">＋</button>
 <button type="button" @click="removeFromCart(idx)" style="margin-left:0.25rem; background:transparent; border:0; color:#525252; cursor:pointer;">×</button>
 </div>
 </div>
 </template>
 <div x-show="posCart.length===0" style="padding:1.5rem; text-align:center; font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Cart is empty</div>
 </div>
 <div style="border-top:1px solid #e0e0e0; padding-top:0.75rem; display:flex; flex-direction:column; gap:0.75rem;">
 <input type="hidden" name="subtotal" :value="cartSubtotal()">
 <input type="hidden" name="discount_amount" :value="cartDiscount()">
 <input type="hidden" name="tax_amount" :value="cartTax()">
 <input type="hidden" name="total_amount" :value="cartTotal()">
 <div style="display:flex; justify-content:space-between; align-items:center;">
 <span style="font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Total due</span>
 </div>
 <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:0.375rem;">
 <label style="cursor:pointer;"><input type="radio" name="payment_method" value="cash" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='cash' ? 'background:#161616; color:#fff; border:1px solid #161616; font-weight:600;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.5rem; text-align:center; font-size:0.75rem; font-family:'IBM Plex Sans',sans-serif;">Cash</div></label>
 <label style="cursor:pointer;"><input type="radio" name="payment_method" value="mpesa" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='mpesa' ? 'background:#161616; color:#fff; border:1px solid #161616; font-weight:600;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.5rem; text-align:center; font-size:0.75rem; font-family:'IBM Plex Sans',sans-serif;">M-Pesa</div></label>
 <label style="cursor:pointer;"><input type="radio" name="payment_method" value="card" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='card' ? 'background:#161616; color:#fff; border:1px solid #161616; font-weight:600;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.5rem; text-align:center; font-size:0.75rem; font-family:'IBM Plex Sans',sans-serif;">Card</div></label>
 <label style="cursor:pointer;"><input type="radio" name="payment_method" value="bank_transfer" x-model="posPayMethod" style="display:none;"><div :style="posPayMethod==='bank_transfer' ? 'background:#161616; color:#fff; border:1px solid #161616; font-weight:600;' : 'background:#fff; border:1px solid #e0e0e0; color:#525252;'" style="padding:0.5rem; text-align:center; font-size:0.75rem; font-family:'IBM Plex Sans',sans-serif;">Bank</div></label>
 </div>
 <button type="submit" :disabled="posCart.length===0" style="width:100%; height:2.5rem; justify-content:center; font-family:'IBM Plex Sans',sans-serif; font-weight:700; border:1px solid transparent; letter-spacing:0.02em;" :style="posCart.length===0 ? 'background:#c6c6c6 !important; border-color:#c6c6c6 !important; color:#525252 !important; opacity:1; pointer-events:none;' : 'background:#24a148 !important; border-color:#198038 !important; color:#ffffff !important; opacity:1; box-shadow:0 2px 6px rgba(36,161,72,0.3);'">✓ Complete sale & print invoice</button>
 </div>
 </div>
 </div>
 </form>
 </div>
</div>

@if(session('success'))
<x-success-popup :message="session('success')" :receiptId="session('receipt_id')" />
@endif

<div x-show="showQuoteModal" x-cloak class="cds--modal is-visible" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(22,22,22,0.6);">
 <div @click.away="showQuoteModal=false" class="cds--modal-container" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:42rem; max-height:90vh; overflow-y:auto; box-shadow:0 16px 48px rgba(0,0,0,0.2);">
 <div class="cds--modal-header" style="background:#fff; border-bottom:1px solid #e0e0e0; padding:0 1rem; height:3rem; display:flex; align-items:center; justify-content:space-between;">
 <div style="display:flex; align-items:center; gap:0.5rem; font-weight:600; font-family:'IBM Plex Sans',sans-serif;">◧ Generate proforma / quotation modal — </div>
 <button @click="showQuoteModal=false" style="background:transparent; border:0; cursor:pointer; font-size:1.5rem;">×</button>
 </div>
 <form action="{{ route('sales.quotations.store') }}" method="POST" style="padding:1rem; display:flex; flex-direction:column; gap:1rem;" x-data="{ quoteItems:[{item_name:'',quantity:1,unit_price:0}], addQuoteItem(){this.quoteItems.push({item_name:'',quantity:1,unit_price:0});}, removeQuoteItem(idx){if(this.quoteItems.length>1)this.quoteItems.splice(idx,1);}, quoteTotal(){return this.quoteItems.reduce((s,it)=>s+(Number(it.quantity||0)*Number(it.unit_price||0)),0);} }">
 @csrf
 <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
 <div><label style="font-size:0.75rem; font-weight:400; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Client / Company name *</label><input type="text" name="customer_name" required class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 <div><label style="font-size:0.75rem; font-weight:400; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Organization</label><input type="text" name="company" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 <div><label style="font-size:0.75rem; font-weight:400; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Phone</label><input type="text" name="phone" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 <div><label style="font-size:0.75rem; font-weight:400; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Valid until</label><input type="date" name="valid_until" value="{{ now()->addDays(14)->format('Y-m-d') }}" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 </div>
 <div style="border-top:1px solid #e0e0e0; padding-top:1rem;">
 <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem;"><span style="font-size:0.75rem; font-weight:600; letter-spacing:0.02em; text-transform:uppercase; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Quotation line items</span><button type="button" @click="addQuoteItem()" style="background:transparent; border:0; color:#0f62fe; font-weight:600; cursor:pointer; font-size:0.75rem; font-family:'IBM Plex Sans',sans-serif;">＋ Add line</button></div>
 <template x-for="(it, idx) in quoteItems" :key="idx">
 <div style="display:grid; grid-template-columns:6fr 2fr 3fr 1fr; gap:0.5rem; align-items:center; margin-bottom:0.5rem;">
 <input type="text" :name="'items[' + idx + '][item_name]'" x-model="it.item_name" placeholder="Item description / service" required class="cds--text-input" style="height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
 <input type="number" :name="'items[' + idx + '][quantity]'" x-model="it.quantity" min="1" required class="cds--text-input" style="height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
 <input type="number" :name="'items[' + idx + '][unit_price]'" x-model="it.unit_price" min="0" required class="cds--text-input" style="height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;">
 <button type="button" @click="removeQuoteItem(idx)" style="height:2rem; background:transparent; border:0; color:#525252; cursor:pointer;">×</button>
 </div>
 </template>
 </div>
 <div class="cds--modal-footer" style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e0e0e0; padding-top:1rem;">
 <div style="font-weight:600; font-family:'IBM Plex Sans',sans-serif;">Total <span style="font-family:'IBM Plex Mono',monospace;" x-text="'TZS ' + quoteTotal().toLocaleString()"></span></div>
 <div style="display:flex; gap:0.5rem;"><button type="button" @click="showQuoteModal=false" class="cds--btn cds--btn--secondary" style="font-family:'IBM Plex Sans',sans-serif;">Cancel</button><button type="submit" class="cds--btn cds--btn--primary" style="font-family:'IBM Plex Sans',sans-serif;">Save & generate quote</button></div>
 </div>
 </form>
 </div>
</div>

{{-- Carbon modals: StockAlert, SalesFeedback, CompleteService --}}
<div x-show="showStockAlert" x-cloak @keydown.escape.window="closeStockAlert()" style="position:fixed; inset:0; z-index:9001; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
  <div x-trap.noscroll="showStockAlert" role="dialog" aria-modal="true" aria-labelledby="stock-alert-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeStockAlert()">
    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
      <h3 id="stock-alert-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Stock notice</h3>
      <button @click="closeStockAlert()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
    </div>
    <div style="padding:1.25rem; background:#fff;">
      <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;" x-text="stockAlertMessage"></p>
    </div>
    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
      <button x-ref="stockAlertClose" @click="closeStockAlert()" type="button" style="height:2.25rem; padding:0 1rem; background:#0f62fe; border:1px solid #0f62fe; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">OK</button>
    </div>
  </div>
</div>

<div x-show="showSalesFeedback" x-cloak @keydown.escape.window="closeSalesFeedback()" style="position:fixed; inset:0; z-index:9001; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
  <div x-trap.noscroll="showSalesFeedback" role="dialog" aria-modal="true" aria-labelledby="sales-feedback-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeSalesFeedback()">
    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
      <h3 id="sales-feedback-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;" x-text="salesFeedbackTitle || (salesFeedbackIsError ? 'Error' : 'Notice')"></h3>
      <button @click="closeSalesFeedback()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
    </div>
    <div style="padding:1.25rem; background:#fff;">
      <p style="font-size:0.875rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;" x-text="salesFeedbackMessage"></p>
    </div>
    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
      <button x-ref="salesFeedbackClose" @click="closeSalesFeedback()" type="button" :style="salesFeedbackIsError ? 'height:2.25rem; padding:0 1rem; background:#da1e28; border:1px solid #da1e28; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:IBM Plex Sans,sans-serif;' : 'height:2.25rem; padding:0 1rem; background:#0f62fe; border:1px solid #0f62fe; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:IBM Plex Sans,sans-serif;'" x-text="salesFeedbackIsError ? 'Close' : 'OK'"></button>
    </div>
  </div>
</div>

<div x-show="showCompleteModal" x-cloak @keydown.escape.window="closeCompleteModal()" style="position:fixed; inset:0; z-index:9001; display:flex; align-items:center; justify-content:center; background:rgba(22,22,22,0.5); padding:1rem;">
  <div x-trap.noscroll="showCompleteModal" role="dialog" aria-modal="true" aria-labelledby="complete-service-title" class="cds--modal" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:28rem; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2);" @click.away="closeCompleteModal()">
    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; justify-content:space-between;">
      <div>
        <h3 id="complete-service-title" style="font-size:1rem; font-weight:600; color:#161616; font-family:'IBM Plex Sans',sans-serif;">Complete service</h3>
        <p style="font-size:0.75rem; color:#525252; margin-top:0.125rem; font-family:'IBM Plex Sans',sans-serif;" x-text="completeVisit ? ((completeVisit.visitor || 'customer') + ' — ' + (completeVisit.service_name || completeVisit.purpose || 'Service')) : ''"></p>
      </div>
      <button @click="closeCompleteModal()" type="button" style="width:2rem; height:2rem; background:transparent; border:0; display:flex; align-items:center; justify-content:center; color:#525252; cursor:pointer;" aria-label="Close"><span class="material-symbols-outlined" style="font-size:18px;">close</span></button>
    </div>
    <div style="padding:1.25rem; background:#fff; display:flex; flex-direction:column; gap:1rem;">
      <div>
        <label for="complete-price" style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Billable amount (TZS) *</label>
        <input x-ref="completePriceInput" id="complete-price" type="number" x-model="completePrice" min="0" placeholder="50000" style="width:100%; height:2.5rem; padding:0 0.75rem; border:1px solid #8d8d8d; background:#f4f4f4; font-size:0.875rem; font-family:'IBM Plex Mono',monospace; margin-top:0.25rem;">
        <p x-show="completePriceError" x-text="completePriceError" style="font-size:0.75rem; color:#da1e28; margin-top:0.25rem;"></p>
      </div>
      <div>
        <label for="complete-notes" style="font-size:0.75rem; font-weight:600; color:#525252; font-family:'IBM Plex Sans',sans-serif;">Resolution notes</label>
        <textarea id="complete-notes" x-model="completeNotes" rows="3" placeholder="Completed by Sales" style="width:100%; border:1px solid #8d8d8d; padding:0.75rem; font-size:0.875rem; background:#fff; font-family:'IBM Plex Sans',sans-serif; margin-top:0.25rem;"></textarea>
      </div>
    </div>
    <div style="padding:1rem 1.25rem; background:#f4f4f4; border-top:1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:0.5rem;">
      <button @click="closeCompleteModal()" type="button" style="height:2.25rem; padding:0 1rem; background:#fff; border:1px solid #8d8d8d; color:#161616; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
      <button @click="submitCompleteModal()" type="button" style="height:2.25rem; padding:0 1rem; background:#0f62fe; border:1px solid #0f62fe; color:#fff; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">Confirm</button>
    </div>
  </div>
</div>

<div x-show="showCustomItemModal" x-cloak class="cds--modal is-visible" style="position:fixed; inset:0; z-index:9000; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(22,22,22,0.5);">
  <div @click.away="showCustomItemModal=false" class="cds--modal-container" style="background:#fff; border:1px solid #e0e0e0; width:100%; max-width:25rem; padding:1rem; display:flex; flex-direction:column; gap:1rem;">
  <div style="font-weight:600; font-family:'IBM Plex Sans',sans-serif; border-top:1px solid #e0e0e0; border-left:1px solid #e0e0e0; padding-left:0.75rem;">Add custom ad-hoc charge dialog — </div>
 <div style="display:flex; flex-direction:column; gap:0.75rem;">
 <div><label style="font-size:0.75rem; color:#525252; font-weight:400; font-family:'IBM Plex Sans',sans-serif;">Item / Service description</label><input type="text" x-model="customItem.name" placeholder="e.g. Special Cable Repair" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
 <div><label style="font-size:0.75rem; color:#525252; font-weight:400; font-family:'IBM Plex Sans',sans-serif;">Price (TZS)</label><input type="number" x-model="customItem.price" placeholder="50000" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 <div><label style="font-size:0.75rem; color:#525252; font-weight:400; font-family:'IBM Plex Sans',sans-serif;">Quantity</label><input type="number" x-model="customItem.qty" value="1" class="cds--text-input" style="width:100%; height:2rem; padding:0 0.5rem; border:0; border-bottom:1px solid #8d8d8d; background:#f4f4f4; margin-top:0.25rem; font-size:0.875rem; font-family:'IBM Plex Sans',sans-serif;"></div>
 </div>
 </div>
 <div class="cds--modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem; border-top:1px solid #e0e0e0; padding-top:1rem;">
 <button type="button" @click="showCustomItemModal=false" class="cds--btn cds--btn--secondary" style="font-family:'IBM Plex Sans',sans-serif;">Cancel</button>
 <button type="button" @click="addCustomItemToCart()" class="cds--btn cds--btn--primary" style="font-family:'IBM Plex Sans',sans-serif;">Add to cart</button>
 </div>
 </div>
</div>

</div>
 </div>
</div>


</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
 const ctxWeekly=document.getElementById('weeklySalesChart');
 if(ctxWeekly){
 const trendData=@json($weeklyTrend ?? []);
 const labels=trendData.map(d=>d.day+' ('+d.date+')');
 const amounts=trendData.map(d=>d.amount);
 window.salesVelocityChart=new Chart(ctxWeekly,{
 type:'bar',
 data:{ labels: labels.length?labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], datasets:[{ label:'Daily Sales (TZS)', data: amounts.length?amounts:[0,0,0,0,0,0,0], backgroundColor:'#0f62fe', hoverBackgroundColor:'#0353e9', borderWidth:0, borderRadius:0, barPercentage:0.55, categoryPercentage:0.7 }]},
 options:{
 responsive:true, maintainAspectRatio:false,
 plugins:{
 legend:{display:false},
 tooltip:{ backgroundColor:'#161616', titleFont:{family:'IBM Plex Sans',size:12}, bodyFont:{family:'IBM Plex Mono',size:12}, padding:12, cornerRadius:0, callbacks:{label:function(ctx){return 'TZS ' + Number(ctx.raw).toLocaleString();}} }
 },
 scales:{
 x:{ grid:{display:false}, ticks:{font:{family:'IBM Plex Sans',size:11}, color:'#525252'}, border:{display:false}},
 y:{ beginAtZero:true, grid:{color:'#e0e0e0'}, ticks:{font:{family:'IBM Plex Mono',size:11}, color:'#525252', callback:function(v){ if(v>=1000000) return (v/1000000)+'M'; if(v>=1000) return (v/1000)+'K'; return v; }}, border:{display:false}}
 }
 }
 });
 }
});
</script>

@endsection
