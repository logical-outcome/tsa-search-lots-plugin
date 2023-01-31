document.addEventListener("DOMContentLoaded",function() {

  let isMatch = false;
  if(document.getElementById('zipform')) {

    document.getElementById('zipform').addEventListener('submit', function(e) {

      e.preventDefault();
      
    });

    document.getElementById('zipform').addEventListener('keyup', function(e) {
      
      
      e.preventDefault();

      const icon = document.getElementById("working-icon");

      icon.className = "show-working"; // this shows the loading icon

      const sto = setTimeout(() => { 
        icon.classList.remove('show-working');
      },5000);

      let zip = document.getElementById('zipcode').value; //the given zip code

      let distance = (document.getElementById('lot-distance').value == 'undefinded') ? 50 : document.getElementById('lot-distance').value;

      let msg = document.getElementById("notice");

      let status = false;

      let lots = document.getElementById('inventory-list').getElementsByTagName('li');

      if(zip.length < 5) {

        resetLots(lots,msg);

        return false;

      }

      fetch('https://www.zipcodeapi.com/rest/js-qo2cagfhXO7BETNGCSEIED8RyQuFSIcjajp4f6kUjbpPgeVB4RQvLgEjManhBbQy/radius.json/'+zip+'/'+distance+'/miles?minimal').then((response) => response.json()).then((data) => {

      //------- test data 
      //data = { zip_codes: [],};
      //------- end test data

          //checking for errors
          // if( Object.keys(data).length && data.error_msg) {

          //   msg.innerHTML = '<h3 id="error">Sorry, and error has occured. This appears to be an incorrect zip code.</h3>';

          //   return false;

          // }
        
          clearTimeout(sto);

          data.zip_codes.push(zip); // we need to add the zip code they entered to the returned zips.
        
          for(lot of lots) {
          
            // reset all lots
            lot.classList.remove('active');

            lot.classList.remove('hidden');
            // end reset
            
            if( data.zip_codes.includes( lot.getAttribute('zip')) ) {

              status = true;

              lot.className = "active";

            }
            else {
              lot.className = "hidden";
            }

            let storeId = lot.getAttribute("storeid");
            
            lot.children[1].firstChild.setAttribute("href","https://app.montanashedcenter.com/inventory-storefront?store_id="+storeId);

          }
          if(status === false) {
            resetLots(lots,msg)
          } 
          if(status === true) {
            msg.innerHTML = "";
          }
          
      });

    });
  }
  function resetLots(lots,msg) {
    for(lot of lots) {

      lot.classList.remove("hidden");

      lot.classList.remove("active");

      msg.innerHTML = '<h3 id="nolot">We are sorry! There appears to be no inventory near this zip code.</h3><p>Please use the chat at the lower right to inquire about a building for you.</p>';

    }
  }
  // this is for those who click on lots that are not active.
  for( lot of document.getElementsByClassName('alot') ) {
        
    
    lot.addEventListener('click', function(e) {
      
      if(!e.currentTarget.classList.contains('active')) {
        
        const li = e.currentTarget.classList;

        li.add("dontclick");

        setTimeout(() => {
         
          removeDontClick(li);

         },5000);

      }
      
    });
   
  }

  function removeDontClick(li) {

    li.remove('dontclick');

  }


});