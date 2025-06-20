(() => {
  'use strict';

  const categorias = window.categoriasData || [];

  // Perfil - atualização de senha
  document.getElementById('profileForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!newPassword || !confirmPassword) {
      Swal.fire({ icon: 'error', title: 'Erro!', text: 'As senhas não podem estar vazias' });
      return;
    }
    if (newPassword !== confirmPassword) {
      Swal.fire({ icon: 'error', title: 'Erro!', text: 'As senhas não coincidem' });
      return;
    }

    fetch('./ajax/update_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `newPassword=${encodeURIComponent(newPassword)}&confirmPassword=${encodeURIComponent(confirmPassword)}`,
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Senha atualizada com sucesso!' }).then(() => {
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
          });
        } else {
          throw new Error(data.message || 'Erro ao atualizar senha');
        }
      })
      .catch((err) => {
        Swal.fire({ icon: 'error', title: 'Erro!', text: err.message });
      });
  });

  // Alteração de função
  document.querySelectorAll('.role-select').forEach((select) => {
    select.addEventListener('change', function () {
      const userId = this.dataset.userId;
      const role = this.value;
      Swal.fire({ title: 'Salvando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      fetch('./ajax/save_user_role.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${userId}&role=${role}`,
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.error) throw new Error(data.error);
          Swal.fire({ icon: 'success', title: 'Função atualizada com sucesso!', showConfirmButton: false, timer: 1500 });
        })
        .catch((err) => {
          console.error('Erro:', err);
          this.value = this.value === 'user' ? 'admin' : 'user';
          Swal.fire({ icon: 'error', title: 'Erro ao salvar função', text: err.message });
        });
    });
  });

  // Alteração de permissões
  document.querySelectorAll('.permission-check').forEach((checkbox) => {
    checkbox.addEventListener('change', function () {
      const userId = this.dataset.userId;
      const permission = this.dataset.permission;
      const value = this.checked;
      Swal.fire({ title: 'Salvando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      fetch('./ajax/save_permissions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_id: userId, permission, value }).toString(),
      })
        .then((r) => {
          if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
          return r.json();
        })
        .then((data) => {
          if (data.error) throw new Error(data.error);
          Swal.fire({ icon: 'success', title: 'Permissão atualizada com sucesso!', showConfirmButton: false, timer: 1500 });
        })
        .catch((err) => {
          console.error('Erro:', err);
          this.checked = !value;
          Swal.fire({ icon: 'error', title: 'Erro ao salvar permissão', text: err.message });
        });
    });
  });

  // Categoria helpers
  window.novaCategoria = function () {
    Swal.fire({
      title: 'Nova Categoria',
      html: '<input type="text" id="categoria_nome" class="swal2-input" placeholder="Nome da categoria">',
      showCancelButton: true,
      confirmButtonText: 'Salvar',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const nome = document.getElementById('categoria_nome').value;
        if (!nome) Swal.showValidationMessage('Por favor, insira um nome para a categoria');
        return { nome };
      },
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/add_categoria.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `nome=${encodeURIComponent(result.value.nome)}`,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              Swal.fire('Sucesso!', 'Categoria adicionada com sucesso!', 'success').then(() => location.reload());
            } else {
              throw new Error(data.message || 'Erro ao adicionar categoria');
            }
          })
          .catch((err) => Swal.fire('Erro!', err.message, 'error'));
      }
    });
  };

  window.editarCategoria = function (categoria) {
    Swal.fire({
      title: 'Editar Categoria',
      html: `<input type="text" id="categoria_nome" class="swal2-input" value="${categoria.item}">`,
      showCancelButton: true,
      confirmButtonText: 'Salvar',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const nome = document.getElementById('categoria_nome').value;
        if (!nome) Swal.showValidationMessage('Por favor, insira um nome para a categoria');
        return { nome };
      },
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/edit_categoria.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${categoria.id}&nome=${encodeURIComponent(result.value.nome)}`,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              Swal.fire('Sucesso!', 'Categoria atualizada com sucesso!', 'success').then(() => location.reload());
            } else {
              throw new Error(data.message || 'Erro ao atualizar categoria');
            }
          })
          .catch((err) => Swal.fire('Erro!', err.message, 'error'));
      }
    });
  };

  window.excluirCategoria = function (id) {
    Swal.fire({
      title: 'Confirmar exclusão',
      text: 'Esta ação não pode ser desfeita!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, excluir!',
      cancelButtonText: 'Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/delete_categoria.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${id}`,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              Swal.fire('Sucesso!', 'Categoria excluída com sucesso!', 'success').then(() => location.reload());
            } else {
              throw new Error(data.message || 'Erro ao excluir categoria');
            }
          })
          .catch((err) => Swal.fire('Erro!', err.message, 'error'));
      }
    });
  };

  const categoriaOptions = (produto = null) => {
    return categorias
      .map((cat) => `<option value="${cat.id}" ${produto && produto.categoria_id == cat.id ? 'selected' : ''}>${cat.item}</option>`)
      .join('');
  };

  window.novoProduto = function () {
    Swal.fire({
      title: 'Novo Produto',
      html: `
        <input type="text" id="produto_nome" class="swal2-input" placeholder="Nome do produto">
        <input type="number" id="produto_valor" class="swal2-input" placeholder="Valor" step="0.01">
        <select id="produto_categoria" class="swal2-input">
          <option value="">Selecione uma categoria</option>
          ${categoriaOptions()}
        </select>
      `,
      showCancelButton: true,
      confirmButtonText: 'Salvar',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const nome = document.getElementById('produto_nome').value;
        const valor = document.getElementById('produto_valor').value;
        const categoria = document.getElementById('produto_categoria').value;
        if (!nome || !valor || !categoria) Swal.showValidationMessage('Por favor, preencha todos os campos');
        return { nome, valor, categoria };
      },
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/add_produto.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `nome=${encodeURIComponent(result.value.nome)}&valor=${result.value.valor}&categoria=${result.value.categoria}`,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              Swal.fire('Sucesso!', 'Produto adicionado com sucesso!', 'success').then(() => location.reload());
            } else {
              throw new Error(data.message || 'Erro ao adicionar produto');
            }
          })
          .catch((err) => Swal.fire('Erro!', err.message, 'error'));
      }
    });
  };

  window.editarProduto = function (produto) {
    Swal.fire({
      title: 'Editar Produto',
      html: `
        <input type="text" id="produto_nome" class="swal2-input" value="${produto.item}">
        <input type="number" id="produto_valor" class="swal2-input" value="${produto.valor}" step="0.01">
        <select id="produto_categoria" class="swal2-input">
          ${categoriaOptions(produto)}
        </select>
      `,
      showCancelButton: true,
      confirmButtonText: 'Salvar',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const nome = document.getElementById('produto_nome').value;
        const valor = document.getElementById('produto_valor').value;
        const categoria = document.getElementById('produto_categoria').value;
        if (!nome || !valor || !categoria) Swal.showValidationMessage('Por favor, preencha todos os campos');
        return { nome, valor, categoria };
      },
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/edit_produto.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${produto.id}&nome=${encodeURIComponent(result.value.nome)}&valor=${result.value.valor}&categoria=${result.value.categoria}`,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              Swal.fire('Sucesso!', 'Produto atualizado com sucesso!', 'success').then(() => location.reload());
            } else {
              throw new Error(data.message || 'Erro ao atualizar produto');
            }
          })
          .catch((err) => Swal.fire('Erro!', err.message, 'error'));
      }
    });
  };

  window.excluirProduto = function (id) {
    Swal.fire({
      title: 'Confirmar exclusão',
      text: 'Esta ação não pode ser desfeita!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, excluir!',
      cancelButtonText: 'Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('./ajax/delete_produto.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${id}`,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              Swal.fire('Sucesso!', 'Produto excluído com sucesso!', 'success').then(() => location.reload());
            } else {
              throw new Error(data.message || 'Erro ao excluir produto');
            }
          })
          .catch((err) => Swal.fire('Erro!', err.message, 'error'));
      }
    });
  };

  window.indisponibilizarProduto = function (id) {
    Swal.fire({
      title: 'Indisponibilizar produto?',
      text: 'O produto ficará indisponível para pedidos',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, indisponibilizar',
      cancelButtonText: 'Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('./ajax/indisponibilizar_produto.php', { id })
          .done((response) => {
            if (response.success) {
              Swal.fire({ icon: 'success', title: 'Produto indisponibilizado!', showConfirmButton: false, timer: 1500 }).then(() => {
                window.location.reload();
              });
            } else {
              Swal.fire({ icon: 'error', title: 'Erro!', text: response.message });
            }
          })
          .fail(() => {
            Swal.fire({ icon: 'error', title: 'Erro!', text: 'Erro ao processar requisição' });
          });
      }
    });
  };

  window.disponibilizarProduto = function (id) {
    Swal.fire({
      title: 'Disponibilizar produto?',
      text: 'O produto voltará a ficar disponível para pedidos',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, disponibilizar',
      cancelButtonText: 'Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('./ajax/disponibilizar_produto.php', { id })
          .done((response) => {
            if (response.success) {
              Swal.fire({ icon: 'success', title: 'Produto disponibilizado!', showConfirmButton: false, timer: 1500 }).then(() => {
                window.location.reload();
              });
            } else {
              Swal.fire({ icon: 'error', title: 'Erro!', text: response.message });
            }
          })
          .fail(() => {
            Swal.fire({ icon: 'error', title: 'Erro!', text: 'Erro ao processar requisição' });
          });
      }
    });
  };

  document.getElementById('chaveSecretaForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    try {
      const formData = new FormData(this);
      const response = await fetch('./ajax/save_chave_secreta.php', { method: 'POST', body: formData });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) throw new TypeError('Oops, não recebemos JSON!');
      const data = await response.json();
      if (data.success) {
        Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.message || 'Chave secreta atualizada com sucesso', showConfirmButton: false, timer: 1500 });
      } else {
        throw new Error(data.message || 'Erro ao atualizar chave secreta');
      }
    } catch (error) {
      console.error('Error:', error);
      Swal.fire({ icon: 'error', title: 'Erro!', text: error.message || 'Erro ao atualizar chave secreta' });
    }
  });

  document.getElementById('btnEditarEstabelecimento')?.addEventListener('click', () => {
    if (typeof editarEstabelecimento === 'function') editarEstabelecimento();
  });

  document.getElementById('allowRegistration')?.addEventListener('change', function () {
    const isChecked = this.checked;
    fetch('./ajax/toggle_registration.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `allow=${isChecked ? 1 : 0}`,
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Configuração atualizada!',
            text: `Registro de novos usuários ${isChecked ? 'habilitado' : 'desabilitado'} com sucesso!`,
            showConfirmButton: false,
            timer: 1500,
          });
        } else {
          throw new Error(data.message || 'Erro ao atualizar configuração');
        }
      })
      .catch((err) => {
        Swal.fire({ icon: 'error', title: 'Erro!', text: err.message });
        this.checked = !isChecked;
      });
  });

  document.addEventListener('DOMContentLoaded', () => {
    const lastSettingsTab = localStorage.getItem('settingsLastTab');
    if (lastSettingsTab) {
      const tab = new bootstrap.Tab(document.querySelector(lastSettingsTab));
      tab.show();
    }

    document.querySelectorAll('#settingsTabs .nav-link').forEach((tabEl) => {
      tabEl.addEventListener('click', function () {
        localStorage.setItem('settingsLastTab', '#' + this.id);
        localStorage.setItem('activeTab', 'settings');
        document.querySelectorAll('.menu-item').forEach((item) => {
          item.classList.remove('active');
          if (item.getAttribute('data-tab') === 'settings') item.classList.add('active');
        });
        const bsTab = new bootstrap.Tab(this);
        bsTab.show();
      });
    });

    const settingsMenuItem = document.querySelector('.menu-item[data-tab="settings"]');
    if (settingsMenuItem) settingsMenuItem.classList.add('active');
  });
})();
