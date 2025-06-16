document.addEventListener('DOMContentLoaded',function(){
  const quiz=document.getElementById('hprl-quiz');
  if(!quiz) return;
  const steps=Array.from(quiz.querySelectorAll('.hprl-step'));
  let resultId=null;
  let saveAnswersPromise=null;
  const STORAGE_KEY='hprl_quiz_state';
  let currentStep=0;
  const debugMode=!!hprlData.debug;
  const debugContainer=document.getElementById('hprl-debug-container');
  const debugToggle=document.getElementById('hprl-debug-toggle');
  const debugLog=document.getElementById('hprl-debug-log');
  const noteBox=document.getElementById('hprl-note');
  const explBox=document.getElementById('hprl-explanations');
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
    if(!id || !hprlData.products || !hprlData.products[id]){
      btn.style.display='none';
      return;
    }
    btn.style.display='flex';
    btn.dataset.product=id;
    const info=hprlData.products[id];
    const img=btn.querySelector('img');
    if(img){
      if(info.img){img.src=info.img;img.style.display='';}else{img.style.display='none';}
    }
    const price=btn.querySelector('.hprl-price');
    if(price) price.innerHTML=info.price;
    const nameEl=btn.querySelector('.hprl-name');
    if(nameEl) nameEl.textContent=info.name;
  }
  function updateExplanations(html){
    if(!explBox) return;
    if(html){
      const first=document.getElementById('hprl-first-name').value.trim();
      const last=document.getElementById('hprl-last-name').value.trim();
      const name=(first||last)?(first+' '+last).trim():'';
      const greeting=name?`Poštovani/a ${name},`:'Poštovani/a,';
      explBox.innerHTML=greeting+'<br>'+html;
      explBox.style.display='block';
    }else{
      explBox.style.display='none';
    }
  }

  const allProducts=new Set();
  hprlData.questions.forEach(q=>{
    if(q.main) allProducts.add(String(q.main));
    if(q.extra) allProducts.add(String(q.extra));
    if(q.package) allProducts.add(String(q.package));
  });

  function applyResults(){
    const yesQuestions=[];
    quiz.querySelectorAll('.hprl-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(sel&&sel.value.toLowerCase()==='da'){yesQuestions.push(parseInt(g.dataset.question));}
    });
    const count={main:{},extra:{},package:{}};
    const notes=[];
    const mentioned=new Set();
    yesQuestions.forEach(i=>{
      const q=hprlData.questions[i];
      if(!q) return;
      if(q.main) count.main[q.main]=(count.main[q.main]||0)+1;
      if(q.extra) count.extra[q.extra]=(count.extra[q.extra]||0)+1;
      if(q.package) count.package[q.package]=(count.package[q.package]||0)+1;
      if(q.main) mentioned.add(String(q.main));
      if(q.extra) mentioned.add(String(q.extra));
      if(q.package) mentioned.add(String(q.package));
      if(q.note) notes.push(q.note);
    });
    function top(obj){let k=null,m=0;Object.keys(obj).forEach(key=>{if(obj[key]>m){m=obj[key];k=key;}});return k;}
    let main=top(count.main)||'';
    let extra=top(count.extra)||'';
    let pack=top(count.package)||'';
    let universal='';
    if(hprlData.universal){
      const allMentioned=[...allProducts].every(p=>mentioned.has(String(p)));
      if(allMentioned){
        universal=hprlData.universal;
      }
    }
    updateProductInfo('main',main);
    updateProductInfo('extra',extra);
    updateProductInfo('package',pack);
    updateProductInfo('universal',universal);
    updateNote('');
    updateExplanations(notes.join('<br>'));
  }
  function saveState(){
    try{
      const state={
        step:currentStep,
        resultId:resultId,
        first_name:document.getElementById('hprl-first-name').value,
        last_name:document.getElementById('hprl-last-name').value,
        email:document.getElementById('hprl-email').value,
        phone:document.getElementById('hprl-phone').value,
        year:document.getElementById('hprl-year').value,
        location:document.getElementById('hprl-location').value,
        answers:{}
      };
      quiz.querySelectorAll('.hprl-question-group').forEach(g=>{
        const sel=g.querySelector('input:checked');
        if(sel) state.answers[g.dataset.question]=sel.dataset.index;
      });
      localStorage.setItem(STORAGE_KEY,JSON.stringify(state));
    }catch(e){}
  }
  function loadState(){
    try{
      const saved=JSON.parse(localStorage.getItem(STORAGE_KEY)||'null');
      if(!saved) return;
      if(saved.first_name) document.getElementById('hprl-first-name').value=saved.first_name;
      if(saved.last_name) document.getElementById('hprl-last-name').value=saved.last_name;
      if(saved.email) document.getElementById('hprl-email').value=saved.email;
      if(saved.phone) document.getElementById('hprl-phone').value=saved.phone;
      if(saved.year) document.getElementById('hprl-year').value=saved.year;
      if(saved.location) document.getElementById('hprl-location').value=saved.location;
      if(saved.answers){
        Object.keys(saved.answers).forEach(q=>{
          const input=quiz.querySelector('.hprl-question-group[data-question="'+q+'"] input[data-index="'+saved.answers[q]+'"]');
          if(input) input.checked=true;
        });
      }
      if(saved.resultId) resultId=saved.resultId;
      currentStep=Math.min(saved.step||0,steps.length-1);
      applyResults();
      showStep(currentStep);
    }catch(e){}
  }
  function showStep(index){
    currentStep=index;
    steps.forEach((s,i)=>{s.style.display=i===index?'block':'none';});
    saveState();
    steps[index].scrollIntoView({behavior:'smooth',block:'start'});
    window.scrollTo({top:0,behavior:'smooth'});
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
      saveState();
    });
  });
  quiz.querySelectorAll('.hprl-question-group input').forEach(inp=>{
    inp.addEventListener('change',()=>{
      const err=inp.closest('.hprl-question-group').querySelector('.hprl-error');
      if(err){err.textContent='';err.style.display='none';}
      saveState();
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
        applyResults();
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
              saveState();
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
  quiz.querySelectorAll('.hprl-prev').forEach(btn=>{
    btn.addEventListener('click',function(){
      const stepElem=this.closest('.hprl-step');
      const step=parseInt(stepElem.dataset.step);
      const prev=step-1;
      showStep(prev-1);
    });
  });
  quiz.querySelectorAll('.hprl-select').forEach(btn=>{
    btn.addEventListener('click',async function(){
      try{
        const checkoutData={
          first_name:document.getElementById('hprl-first-name').value.trim(),
          last_name:document.getElementById('hprl-last-name').value.trim(),
          email:document.getElementById('hprl-email').value.trim(),
          phone:document.getElementById('hprl-phone').value.trim(),
          city:document.getElementById('hprl-location').value.trim()
        };
        localStorage.setItem('hprl_checkout',JSON.stringify(checkoutData));
        localStorage.removeItem(STORAGE_KEY);
      }catch(e){}
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
  loadState();
});
