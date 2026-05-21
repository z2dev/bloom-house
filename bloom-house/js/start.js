const optionCards = document.querySelectorAll(".option-card");

optionCards.forEach(function(card){
    card.addEventListener("click", function(){
        card.style.transform = "scale(0.97)";
    });
});