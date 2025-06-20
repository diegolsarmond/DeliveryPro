(() => {
  let clienteModal;

  function aplicarMascaraTelefone(input) {
    input.addEventListener('input', () => {
      let v = input.value.replace(/\D/g, '').slice(0, 11);
      if (v.length >= 11) {
        v = v.replace(/(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
      } else if (v.length >= 10) {
        v = v.replace(/(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
      } else if (v.length > 2) {
        v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
      } else {
        v = v.replace(/(\d*)/, '($1');
      }
      input.value = v;
    });
  }

  function limparFormulario() {
    document.getElementById('clienteForm').reset();
    document.getElementById('clienteId').value = '';
  }

  window.abrirClienteModal = function () {
    limparFormulario();
    clienteModal.show();
    document.getElementById('clienteNome').focus();
    document.getElementById('clienteTelefone').dispatchEvent(new Event('input'));
  };

  window.editarCliente = function (id) {
    fetch(`./ajax/buscar_cliente.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const cliente = data.cliente;
          document.getElementById('clienteId').value = cliente.id;
          document.getElementById('clienteNome').value = cliente.nome;
          document.getElementById('clienteTelefone').value = cliente.telefone;
          document.getElementById('clienteCep').value = cliente.cep;
          document.getElementById('clienteRua').value = cliente.rua;
          document.getElementById('clienteBairro').value = cliente.bairro;
          document.getElementById('clienteComplemento').value = cliente.complemento || '';
          clienteModal.show();
          document.getElementById('clienteTelefone').dispatchEvent(new Event('input'));
          document.getElementById('clienteNome').focus();
        } else {
          throw new Error(data.message);
        }
      })
      .catch((error) => {
        Swal.fire({ icon: 'error', title: 'Erro!', text: error.message });
      });
  };

  window.excluirCliente = function (id) {
    Swal.fire({
      title: 'Confirmar exclusão',
      text: 'Tem certeza que deseja excluir este cliente?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/excluir_cliente.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${id}`,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              window.location.reload();
            } else {
              throw new Error(data.message);
            }
          })
          .catch((error) => {
            Swal.fire({ icon: 'error', title: 'Erro!', text: error.message });
          });
      }
    });
  };

  function initClienteModal() {
    if (clienteModal) return;
    clienteModal = new bootstrap.Modal(document.getElementById('clienteModal'));
    const telefoneInput = document.getElementById('clienteTelefone');
    aplicarMascaraTelefone(telefoneInput);

    document.getElementById('clienteModal').addEventListener('shown.bs.modal', () => {
      telefoneInput.dispatchEvent(new Event('input'));
      document.getElementById('clienteNome').focus();
    });

    document.getElementById('clienteForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('./ajax/salvar_cliente.php', { method: 'POST', body: formData })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Sucesso!',
              text: data.message,
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              window.location.reload();
            });
          } else {
            throw new Error(data.message);
          }
        })
        .catch((error) => {
          Swal.fire({ icon: 'error', title: 'Erro!', text: error.message });
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClienteModal);
  } else {
    initClienteModal();
  }
})();
