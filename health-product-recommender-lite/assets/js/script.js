document.addEventListener('DOMContentLoaded',function(){
  const quiz=document.getElementById('hprl-quiz');
  if(!quiz) return;
  const steps=Array.from(quiz.querySelectorAll('.hprl-step'));
  let resultId=null;
  function showStep(index){
    steps.forEach((s,i)=>{s.style.display=i===index?'block':'none';});
  }
  function gatherIndexes(){
    const indexes=[];
    quiz.querySelectorAll('.hprl-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(sel) indexes.push(sel.dataset.index);
    });
    return indexes;
  }
  function gatherAnswers(){
    const ans=[];
    quiz.querySelectorAll('.hprl-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(sel) ans.push(sel.value);
    });
    return ans;
  }
  quiz.querySelectorAll('.hprl-next').forEach(btn=>{
    btn.addEventListener('click',function(){
      const stepElem=this.closest('.hprl-step');
      const step=parseInt(stepElem.dataset.step);
      if(step===1){
        if(!(document.getElementById('hprl-name').value&&document.getElementById('hprl-email').value&&document.getElementById('hprl-phone').value&&document.getElementById('hprl-year').value))return;
      }else{
        let valid=true;
        stepElem.querySelectorAll('.hprl-question-group').forEach(g=>{if(!g.querySelector('input:checked'))valid=false;});
        if(!valid)return;
      }
      const next=step+1;
      if(next===steps.length){
        const indexes=gatherIndexes();
        let cheap=hprlData.cheap;
        let premium=hprlData.premium;
        const key=indexes.join('|');
        if(hprlData.combos&&hprlData.combos[key]){
          cheap=hprlData.combos[key].cheap;
          premium=hprlData.combos[key].premium;
        }
        quiz.querySelector('.hprl-select[data-type="cheap"]').dataset.product=cheap;
        quiz.querySelector('.hprl-select[data-type="premium"]').dataset.product=premium;
        const data=new FormData();
        data.append('action','hprl_save_answers');
        data.append('nonce',hprlData.nonce);
        data.append('name',document.getElementById('hprl-name').value);
        data.append('email',document.getElementById('hprl-email').value);
        data.append('phone',document.getElementById('hprl-phone').value);
        data.append('birth_year',document.getElementById('hprl-year').value);
        data.append('location',document.getElementById('hprl-location').value);
        gatherAnswers().forEach(a=>data.append('answers[]',a));
        fetch(hprlData.ajaxurl,{method:'POST',body:data,credentials:'same-origin'})
          .then(r=>r.json()).then(res=>{if(res.success)resultId=res.data.result_id;});
      }
      showStep(next-1);
    });
  });
  quiz.querySelectorAll('.hprl-select').forEach(btn=>{
    btn.addEventListener('click',function(){
      const data=new FormData();
      data.append('action','hprl_set_product');
      data.append('nonce',hprlData.nonce);
      data.append('result_id',resultId||0);
      data.append('product',this.dataset.product);
      fetch(hprlData.ajaxurl,{method:'POST',body:data,credentials:'same-origin'})
        .then(()=>{fetch(hprlData.cart_url+'?add-to-cart='+this.dataset.product,{credentials:'same-origin'}).then(()=>{window.location=hprlData.checkout;});});
    });
  });
  showStep(0);
});
