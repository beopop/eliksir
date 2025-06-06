document.addEventListener('DOMContentLoaded',function(){
  try{
    const stored=localStorage.getItem('hprl_checkout');
    if(!stored) return;
    const data=JSON.parse(stored);
    const map={
      first_name:'#billing_first_name',
      last_name:'#billing_last_name',
      email:'#billing_email',
      phone:'#billing_phone',
      city:'#billing_city'
    };
    Object.keys(map).forEach(key=>{
      if(data[key]){
        const el=document.querySelector(map[key]);
        if(el&& !el.value){
          el.value=data[key];
        }
      }
    });
    localStorage.removeItem('hprl_checkout');
  }catch(e){
    console.error('HPRL checkout fill error',e);
  }
});
