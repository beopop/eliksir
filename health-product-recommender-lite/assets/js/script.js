document.addEventListener('DOMContentLoaded', function(){
  const quiz = document.getElementById('hprl-quiz');
  if(!quiz) return;
  const steps = quiz.querySelectorAll('.hprl-step');
  const next1 = document.getElementById('hprl-next1');
  const next2 = document.getElementById('hprl-next2');
  next1.addEventListener('click', function(){
    if(document.getElementById('hprl-name').value && document.getElementById('hprl-email').value && document.getElementById('hprl-phone').value && document.getElementById('hprl-year').value){
      steps[0].style.display='none';
      steps[1].style.display='block';
    }
  });
  next2.addEventListener('click', function(){
    let valid=true;
    const indexes=[];
    quiz.querySelectorAll('.hprl-question-group').forEach(g=>{
      const sel=g.querySelector('input:checked');
      if(!sel){valid=false;return;}
      indexes.push(sel.dataset.index);
    });
    if(!valid) return;
    let cheap=hprlData.cheap;
    let premium=hprlData.premium;
    const key=indexes.join('|');
    if(hprlData.combos && hprlData.combos[key]){
      cheap=hprlData.combos[key].cheap;
      premium=hprlData.combos[key].premium;
    }
    quiz.querySelector('.hprl-select[data-type="cheap"]').dataset.product=cheap;
    quiz.querySelector('.hprl-select[data-type="premium"]').dataset.product=premium;
    steps[1].style.display='none';
    steps[2].style.display='block';
  });
  quiz.querySelectorAll('.hprl-select').forEach(btn=>{
    btn.addEventListener('click', function(){
      const data = new FormData();
      data.append('action','hprl_save_quiz');
      data.append('nonce', hprlData.nonce);
      data.append('name', document.getElementById('hprl-name').value);
      data.append('email', document.getElementById('hprl-email').value);
      data.append('phone', document.getElementById('hprl-phone').value);
      data.append('birth_year', document.getElementById('hprl-year').value);
      data.append('location', document.getElementById('hprl-location').value);
      let answers=[];
      quiz.querySelectorAll('.hprl-question-group').forEach(g=>{
        const sel=g.querySelector('input:checked');
        if(sel) answers.push(sel.value);
      });
      answers.forEach(a=>data.append('answers[]',a));
      data.append('product', this.dataset.product);
      fetch(hprlData.ajaxurl, {method:'POST', body:data, credentials:'same-origin'})
        .then(r=>r.json())
        .then(()=>{
          fetch(hprlData.cart_url+'?add-to-cart='+this.dataset.product,{credentials:'same-origin'})
            .then(()=>{window.location=hprlData.checkout;});
        });
    });
  });
});
