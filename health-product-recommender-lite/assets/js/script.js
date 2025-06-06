document.addEventListener('DOMContentLoaded',function(){
  const quiz=document.getElementById('hprl-quiz');
  if(!quiz) return;
  const steps=Array.from(quiz.querySelectorAll('.hprl-step'));
  let resultId=null;
  let saveAnswersPromise=null;
  const debugMode=!!hprlData.debug;
  const debugContainer=document.getElementById('hprl-debug-container');
  const debugToggle=document.getElementById('hprl-debug-toggle');
  const debugLog=document.getElementById('hprl-debug-log');
  const noteBox=document.getElementById('hprl-note');
  if(debugToggle){
    debugToggle.addEventListener('change',()=>{debugLog.style.display=debugToggle.checked?'block':'none';});
  }
  function showDebug(log){
    if(!debugMode||!debugContainer) return;
    debugContainer.style.display='block';
    debugLog.textContent=log;
  }
  function updateNote(text){
    if(!noteBox) return;
    if(text){
      noteBox.innerHTML=text;
      noteBox.style.display='block';
    }else{
      noteBox.style.display='none';
    }
  }
  function updateProductInfo(type,id){
    const btn=quiz.querySelector('.hprl-select[data-type="'+type+'"]');
    if(!btn) return;
    btn.dataset.product=id;
    if(hprlData.products&&hprlData.products[id]){
      const info=hprlData.products[id];
      const img=btn.querySelector('img');
      if(img&&info.img) img.src=info.img;
      const price=btn.querySelector('.hprl-price');
      if(price) price.innerHTML=info.price;
    }
  }
  function showStep(index){
    steps.forEach((s,i)=>{s.style.display=i===index?'block':'none';});
  }
  function clearErrors(scope){
    scope.querySelectorAll('.hprl-error').forEach(e=>{e.textContent='';e.style.display='none';});
  }
  function showError(input,msg){
    const err=input.parentElement.querySelector('.hprl-error');
    if(err){err.textContent=msg;err.style.display='block';}
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
  quiz.querySelectorAll('input').forEach(inp=>{
    inp.addEventListener('input',()=>{
      const err=inp.parentElement.querySelector('.hprl-error');
      if(err){err.textContent='';err.style.display='none';}
    });
  });
  quiz.querySelectorAll('.hprl-question-group input').forEach(inp=>{
    inp.addEventListener('change',()=>{
      const err=inp.closest('.hprl-question-group').querySelector('.hprl-error');
      if(err){err.textContent='';err.style.display='none';}
    });
  });
  quiz.querySelectorAll('.hprl-next').forEach(btn=>{
    btn.addEventListener('click',async function(){
      const stepElem=this.closest('.hprl-step');
      const step=parseInt(stepElem.dataset.step);
      clearErrors(stepElem);
      if(step===1){
        const firstNameInput=document.getElementById('hprl-first-name');
        const lastNameInput=document.getElementById('hprl-last-name');
        const emailInput=document.getElementById('hprl-email');
        const phoneInput=document.getElementById('hprl-phone');
        const yearInput=document.getElementById('hprl-year');
        const firstName=firstNameInput.value.trim();
        const lastName=lastNameInput.value.trim();
        const email=emailInput.value.trim();
        const phone=phoneInput.value.trim();
        const year=yearInput.value.trim();
        let valid=true;
        if(!firstName){showError(firstNameInput,'Unesite ime.');valid=false;}
        if(!lastName){showError(lastNameInput,'Unesite prezime.');valid=false;}
        if(!email){showError(emailInput,'Unesite email.');valid=false;}
        else if(!/^([^\s@]+)@([^\s@]+)\.[^\s@]+$/.test(email)){showError(emailInput,'Neispravan email.');valid=false;}
        if(!phone){showError(phoneInput,'Unesite telefon.');valid=false;}
        else if(!/^[0-9]+$/.test(phone)){showError(phoneInput,'Telefon mora da sadrzi samo brojeve');valid=false;}
        if(!year){showError(yearInput,'Unesite godinu rodjenja.');valid=false;}
        if(!valid) return;
      }else{
        let valid=true;
        stepElem.querySelectorAll('.hprl-question-group').forEach(g=>{
          if(!g.querySelector('input:checked')){
            const err=g.querySelector('.hprl-error');
            if(err){err.textContent='Odaberite odgovor.';err.style.display='block';}
            valid=false;
          }
        });
        if(!valid) return;
      }
      const next=step+1;
      if(next===steps.length){
        const indexes=gatherIndexes();
        let cheap=hprlData.cheap;
        let premium=hprlData.premium;
        const key=indexes.join('|');
        let note='';
        if(hprlData.combos&&hprlData.combos[key]){
          cheap=hprlData.combos[key].cheap;
          premium=hprlData.combos[key].premium;
          note=hprlData.combos[key].note||'';
        }
        updateProductInfo('cheap',cheap);
        updateProductInfo('premium',premium);
        updateNote(note);
        const data=new FormData();
        data.append('action','hprl_save_answers');
        data.append('nonce',hprlData.nonce);
        data.append('first_name',document.getElementById('hprl-first-name').value);
        data.append('last_name',document.getElementById('hprl-last-name').value);
        data.append('email',document.getElementById('hprl-email').value);
        data.append('phone',document.getElementById('hprl-phone').value);
        data.append('birth_year',document.getElementById('hprl-year').value);
        data.append('location',document.getElementById('hprl-location').value);
        gatherAnswers().forEach(a=>data.append('answers[]',a));
        saveAnswersPromise=fetch(hprlData.ajaxurl,{method:'POST',body:data,credentials:'same-origin'})
          .then(r=>r.json())
          .then(res=>{
            if(res.success){
              resultId=res.data.result_id;
            }else{
              alert(res.data&&res.data.message?res.data.message:'Greška pri snimanju.');
              if(res.data&&res.data.log) showDebug(res.data.log);
            }
          })
          .catch(()=>{alert('Greška pri snimanju.');showDebug('Network error');});
      }
      showStep(next-1);
    });
  });
  quiz.querySelectorAll('.hprl-select').forEach(btn=>{
    btn.addEventListener('click',async function(){
      if(saveAnswersPromise) await saveAnswersPromise;
      const data=new FormData();
      data.append('action','hprl_set_product');
      data.append('nonce',hprlData.nonce);
      data.append('result_id',resultId||0);
      data.append('product',this.dataset.product);
      fetch(hprlData.ajaxurl,{method:'POST',body:data,credentials:'same-origin'})
        .then(r=>r.json())
        .then(res=>{
          if(res.success){
            fetch(hprlData.cart_url+'?add-to-cart='+this.dataset.product,{credentials:'same-origin'})
              .then(()=>{window.location=hprlData.checkout;});
          }else{
            alert('Greška pri dodavanju proizvoda.');
            if(res.data&&res.data.log) showDebug(res.data.log);
          }
        })
        .catch(()=>{alert('Greška pri dodavanju proizvoda.');showDebug('Network error');});
    });
  });
  updateProductInfo('cheap',hprlData.cheap);
  updateProductInfo('premium',hprlData.premium);
  updateNote('');
  showStep(0);
});
