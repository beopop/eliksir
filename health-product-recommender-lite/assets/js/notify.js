jQuery(function($){
  var $notice = $('#hprl-complete-notice');
  if(!$notice.length) return;
  $notice.hide();
  $(document).on('hprlQuizStepChange',function(e,data){
    if(data && data.currentStep === data.stepCount - 1){
      $notice.stop(true,true).slideDown();
    }else{
      $notice.hide();
    }
  });
});
