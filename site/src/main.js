import "./style.css";

document.addEventListener("DOMContentLoaded", () => {
  // ==================================================
  // 1. MENU MOBILE (CORRIGIDO)
  // ==================================================
  // O CSS usa .nav-list, então precisamos pegar essa classe aqui
  const mobileBtn = document.querySelector(".mobile-toggle");
  const navList = document.querySelector(".nav-list");
  const navLinks = document.querySelectorAll(".nav-list a");

  if (mobileBtn && navList) {
    mobileBtn.addEventListener("click", () => {
      // Alterna a classe .active definida no CSS novo
      navList.classList.toggle("active");

      // Animação do ícone (opcional, se usar FontAwesome)
      const icon = mobileBtn.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-bars");
        icon.classList.toggle("fa-times");
      }
    });

    // Fecha o menu ao clicar em um link
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        navList.classList.remove("active");
        // Reseta o ícone se necessário
        const icon = mobileBtn.querySelector("i");
        if (icon) {
          icon.classList.add("fa-bars");
          icon.classList.remove("fa-times");
        }
      });
    });
  }

  // ==================================================
  // 2. LÓGICA DO FORMULÁRIO EM PASSOS (NOVO)
  // ==================================================
  // Isso é necessário porque o CSS esconde os passos 2 e 3
  const steps = document.querySelectorAll(".form-step");
  const nextBtns = document.querySelectorAll(".btn-next");
  const prevBtns = document.querySelectorAll(".btn-prev");
  const indicators = document.querySelectorAll(".step-indicator");
  let currentStep = 0;

  function showStep(n) {
    // Remove active de todos
    steps.forEach((step) => step.classList.remove("active"));
    indicators.forEach((ind) => ind.classList.remove("active"));

    // Ativa o atual
    steps[n].classList.add("active");
    // Ativa indicadores até o atual
    for (let i = 0; i <= n; i++) {
      if (indicators[i]) indicators[i].classList.add("active");
    }
  }

  // Botões "Próximo"
  nextBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Validação simples antes de avançar (opcional)
      const currentInputs = steps[currentStep].querySelectorAll(
        "input[required], select[required]",
      );
      let valid = true;
      currentInputs.forEach((input) => {
        if (!input.checkValidity()) {
          input.reportValidity();
          valid = false;
        }
      });

      if (valid && currentStep < steps.length - 1) {
        currentStep++;
        showStep(currentStep);
      }
    });
  });

  // Botões "Voltar"
  prevBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
      }
    });
  });

  // Inicializa o passo 0
  if (steps.length > 0) {
    showStep(currentStep);
  }

  // ==================================================
  // 3. CAMPOS DINÂMICOS (Ida/Volta & CPF/CNPJ)
  // ==================================================
  const tripTypeSelect = document.getElementById("trip-type");
  const returnFields = document.querySelectorAll(".return-field");
  const returnInputs = document.querySelectorAll(".return-field input");

  function toggleReturnFields() {
    if (!tripTypeSelect) return;
    const isOneWay = tripTypeSelect.value === "one-way";

    returnFields.forEach((field) => {
      // Usa style.display ou classe css, dependendo da sua preferência
      field.style.display = isOneWay ? "none" : "block";
    });

    returnInputs.forEach((input) => {
      if (isOneWay) {
        input.removeAttribute("required");
        input.value = "";
      } else {
        input.setAttribute("required", "true");
      }
    });
  }

  if (tripTypeSelect) {
    tripTypeSelect.addEventListener("change", toggleReturnFields);
    toggleReturnFields();
  }

  // Lógica CPF/CNPJ
  const personTypeSelect = document.getElementById("person-type");
  const docLabel = document.getElementById("doc-label");
  const docInput = document.getElementById("document");

  if (personTypeSelect && docLabel && docInput) {
    personTypeSelect.addEventListener("change", (e) => {
      const isPJ = e.target.value === "pj";
      if (isPJ) {
        docLabel.innerHTML = 'CNPJ <span class="required">*</span>';
        docInput.placeholder = "00.000.000/0000-00";
        // Aplica máscara de CNPJ se tiver biblioteca, ou limpa valor
        docInput.value = "";
      } else {
        docLabel.innerHTML = 'CPF <span class="required">*</span>';
        docInput.placeholder = "000.000.000-00";
        docInput.value = "";
      }
    });
  }

  // ==================================================
  // 4. ENVIO DO FORMULÁRIO
  // ==================================================
  const form = document.getElementById("quote-form");
  const btnSubmit = document.querySelector(".btn-submit");

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const originalText = btnSubmit.innerText;
      btnSubmit.innerText = "Enviando...";
      btnSubmit.disabled = true;

      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      try {
        const response = await fetch("/enviar.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data),
        });

        const result = await response.json();

        if (result.success) {
          window.location.href = "/obrigado.html";
        } else {
          throw new Error(result.message || "Erro no servidor");
        }
      } catch (error) {
        console.error("Erro:", error);
        alert("Ocorreu um erro ao enviar. Tente pelo WhatsApp.");
      } finally {
        btnSubmit.innerText = originalText;
        btnSubmit.disabled = false;
      }
    });
  }
});
