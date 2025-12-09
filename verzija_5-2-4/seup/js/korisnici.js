/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima
 * te ga je izričito zabranjeno umnožavati, distribuirati, mijenjati, objavljivati ili
 * na drugi način eksploatirati bez pismenog odobrenja autora.
 */

/**
 * SEUP Module - Korisnici JavaScript
 * Autocomplete functionality for internal user designations
 */

document.addEventListener("DOMContentLoaded", function() {
  const imeInput = document.getElementById("ime_user");
  const redniBrojInput = document.getElementById("redni_broj");
  const radnoMjestoInput = document.getElementById("radno_mjesto");
  const resultsDiv = document.getElementById("autocomplete-results");

  if (!imeInput || !resultsDiv) {
    return;
  }

  let timeout = null;
  let currentXhr = null;

  // Autocomplete on input
  imeInput.addEventListener("input", function() {
    clearTimeout(timeout);

    // Cancel previous request
    if (currentXhr) {
      currentXhr.abort();
    }

    const search = this.value.trim();

    if (search.length < 3) {
      resultsDiv.style.display = "none";
      resultsDiv.innerHTML = "";
      return;
    }

    timeout = setTimeout(function() {
      // Show loading state
      resultsDiv.innerHTML = '<div style="padding: 10px; color: #666; text-align: center;">Pretraživanje...</div>';
      resultsDiv.style.display = "block";

      // Make AJAX request
      currentXhr = new XMLHttpRequest();
      currentXhr.open("POST", window.location.pathname, true);
      currentXhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      currentXhr.onload = function() {
        if (this.status === 200) {
          try {
            const data = JSON.parse(this.responseText);

            if (data.success && data.results && data.results.length > 0) {
              resultsDiv.innerHTML = "";

              data.results.forEach(function(item) {
                const div = document.createElement("div");
                div.textContent = item.label;
                div.style.padding = "10px";
                div.style.cursor = "pointer";
                div.style.borderBottom = "1px solid #eee";
                div.setAttribute("data-id", item.id);
                div.setAttribute("data-ime", item.ime);
                div.setAttribute("data-rbr", item.rbr);
                div.setAttribute("data-naziv", item.naziv);

                // Hover effect
                div.addEventListener("mouseenter", function() {
                  this.style.background = "#f0f0f0";
                });
                div.addEventListener("mouseleave", function() {
                  this.style.background = "white";
                });

                // Click handler
                div.addEventListener("click", function() {
                  imeInput.value = this.getAttribute("data-ime");

                  if (redniBrojInput) {
                    redniBrojInput.value = this.getAttribute("data-rbr");
                  }

                  if (radnoMjestoInput) {
                    radnoMjestoInput.value = this.getAttribute("data-naziv");
                  }

                  resultsDiv.style.display = "none";
                  resultsDiv.innerHTML = "";
                });

                resultsDiv.appendChild(div);
              });

              resultsDiv.style.display = "block";
            } else {
              resultsDiv.innerHTML = '<div style="padding: 10px; color: #999; text-align: center;">Nema rezultata</div>';
              resultsDiv.style.display = "block";

              setTimeout(function() {
                resultsDiv.style.display = "none";
              }, 2000);
            }
          } catch (e) {
            console.error("Error parsing JSON:", e);
            resultsDiv.style.display = "none";
          }
        } else {
          console.error("Request failed with status:", this.status);
          resultsDiv.style.display = "none";
        }
      };

      currentXhr.onerror = function() {
        console.error("Network error");
        resultsDiv.style.display = "none";
      };

      currentXhr.send("action=autocomplete&search=" + encodeURIComponent(search));
    }, 300);
  });

  // Close autocomplete on outside click
  document.addEventListener("click", function(e) {
    if (e.target !== imeInput && !resultsDiv.contains(e.target)) {
      resultsDiv.style.display = "none";
      resultsDiv.innerHTML = "";
    }
  });

  // Close autocomplete on ESC key
  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape" && resultsDiv.style.display === "block") {
      resultsDiv.style.display = "none";
      resultsDiv.innerHTML = "";
    }
  });

  // Validate redni broj input
  if (redniBrojInput) {
    redniBrojInput.addEventListener("input", function() {
      const value = parseInt(this.value);

      if (value < 0) {
        this.value = 0;
      } else if (value > 99) {
        this.value = 99;
      }
    });

    redniBrojInput.addEventListener("blur", function() {
      const value = this.value.trim();

      if (value === "") {
        return;
      }

      const numValue = parseInt(value);

      if (isNaN(numValue) || numValue < 0 || numValue > 99) {
        alert("Redni broj mora biti između 0 i 99");
        this.focus();
      }
    });
  }

  // Form validation before submit
  const form = document.querySelector(".korisnici-form");

  if (form) {
    form.addEventListener("submit", function(e) {
      const ime = imeInput.value.trim();
      const rbr = redniBrojInput ? redniBrojInput.value.trim() : "";
      const mjesto = radnoMjestoInput ? radnoMjestoInput.value.trim() : "";

      if (!ime) {
        e.preventDefault();
        alert("Ime i prezime je obavezno");
        imeInput.focus();
        return false;
      }

      if (rbr === "" || isNaN(parseInt(rbr))) {
        e.preventDefault();
        alert("Redni broj je obavezan");
        if (redniBrojInput) redniBrojInput.focus();
        return false;
      }

      const rbrNum = parseInt(rbr);
      if (rbrNum < 0 || rbrNum > 99) {
        e.preventDefault();
        alert("Redni broj mora biti između 0 i 99");
        if (redniBrojInput) redniBrojInput.focus();
        return false;
      }

      if (!mjesto) {
        e.preventDefault();
        alert("Radno mjesto je obavezno");
        if (radnoMjestoInput) radnoMjestoInput.focus();
        return false;
      }

      return true;
    });
  }

  // Add confirmation for delete actions
  const deleteLinks = document.querySelectorAll('a[href*="action=delete"]');

  deleteLinks.forEach(function(link) {
    link.addEventListener("click", function(e) {
      if (!confirm("Jeste li sigurni da želite obrisati ovu internu oznaku korisnika?")) {
        e.preventDefault();
        return false;
      }
    });
  });

  // Table row highlight on hover
  const tableRows = document.querySelectorAll(".korisnici-table tbody tr");

  tableRows.forEach(function(row) {
    row.addEventListener("mouseenter", function() {
      this.style.transform = "scale(1.005)";
      this.style.transition = "transform 0.1s";
    });

    row.addEventListener("mouseleave", function() {
      this.style.transform = "scale(1)";
    });
  });

  // Auto-focus first input on page load
  if (imeInput && !imeInput.value) {
    setTimeout(function() {
      imeInput.focus();
    }, 100);
  }
});
