<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testello - Sistema de Importação de Frete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-brand {
            font-weight: bold;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            border-radius: 12px;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .file-upload-area:hover {
            border-color: #007bff;
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
            transform: translateY(-2px);
        }

        .file-upload-area.dragover {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .loading {
            display: none;
        }

        .container {
            max-width: 800px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-truck"></i> Testello Freight</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="fas fa-circle text-success"></i> Sistema Online
                </span>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-2">
                    <i class="fas fa-upload text-primary"></i>
                    Importação de Tabela de Frete
                </h2>
                <p class="text-center text-muted">
                    Faça upload do seu arquivo CSV para importar as tarifas de frete
                </p>
            </div>
        </div>

        <!-- Formulário de Upload -->
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <form id="importForm" enctype="multipart/form-data">
                            @csrf

                            <!-- Área de Upload -->
                            <div class="mb-4">
                                <div class="file-upload-area" onclick="document.getElementById('csvFile').click()">
                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                    <h5 class="mb-2">Clique aqui ou arraste seu arquivo CSV</h5>
                                    <p class="text-muted mb-0">
                                        Formato suportado: CSV com colunas min_weight, max_weight, price
                                    </p>
                                    <small class="text-muted">Tamanho máximo: 50MB</small>
                                </div>
                                <input type="file" id="csvFile" name="csv_file" accept=".csv,.txt" class="d-none" required>
                                <div id="fileInfo" class="mt-3" style="display: none;">
                                    <div class="alert alert-info">
                                        <i class="fas fa-file-csv"></i>
                                        <span id="fileName"></span>
                                        <span class="badge bg-secondary ms-2" id="fileSize"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Informações do Cliente -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="clientName" class="form-label">
                                        <i class="fas fa-building"></i> Nome do Cliente
                                    </label>
                                    <input type="text" class="form-control" id="clientName" name="client_name"
                                        placeholder="Ex: Empresa ABC Ltda" required maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label for="clientDocument" class="form-label">
                                        <i class="fas fa-id-card"></i> CNPJ/CPF
                                    </label>
                                    <input type="text" class="form-control" id="clientDocument" name="client_document"
                                        placeholder="Ex: 12.345.678/0001-95" required maxlength="50">
                                </div>
                            </div>

                            <!-- Botão de Importar -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload"></i> Iniciar Importação
                                </button>
                            </div>
                        </form>

                        <!-- Loading -->
                        <div id="loading" class="text-center loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Processando...</span>
                            </div>
                            <p class="mt-2">Processando arquivo...</p>
                        </div>

                        <!-- Resultado -->
                        <div id="result" class="mt-4" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status de Importação -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-search text-primary"></i>
                            Consultar Status de Importação
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <label for="importId" class="form-label">ID da Importação</label>
                                <input type="number" class="form-control" id="importId"
                                    placeholder="Digite o ID da importação">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary w-100"
                                    onclick="checkStatus()">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                            </div>
                        </div>
                        <div id="statusResult" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listagem de Importações -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-list text-primary"></i>
                            Histórico de Importações
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadImports()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="importsLoading" class="text-center" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <span class="ms-2">Carregando importações...</span>
                        </div>
                        <div id="importsTable" class="table-responsive"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.Laravel = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };

        const uploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('csvFile');
        const form = document.getElementById('importForm');
        const loading = document.getElementById('loading');
        const result = document.getElementById('result');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showFileInfo(files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                showFileInfo(e.target.files[0]);
            }
        });

        function showFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);

            form.style.display = 'none';
            loading.style.display = 'block';
            result.style.display = 'none';

            try {
                const response = await fetch('/api/freight/import', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': window.Laravel.csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showResult('success', 'Importação iniciada com sucesso!',
                        `ID da importação: ${data.data.freight_table_id}`);

                    // Atualizar lista de importações após 2 segundos
                    setTimeout(() => {
                        loadImports();
                    }, 2000);
                } else {
                    showResult('danger', 'Erro na importação',
                        data.message || 'Erro desconhecido');
                }
            } catch (error) {
                showResult('danger', 'Erro de conexão',
                    'Não foi possível conectar com o servidor');
            } finally {
                loading.style.display = 'none';
                result.style.display = 'block';
            }
        });

        function showResult(type, title, message) {
            result.innerHTML = `
                <div class="alert alert-${type}" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                        ${title}
                    </h6>
                    <p class="mb-0">${message}</p>
                    <hr>
                    <button class="btn btn-sm btn-outline-${type === 'success' ? 'success' : 'danger'}"
                            onclick="resetForm()">
                        <i class="fas fa-redo"></i> Nova Importação
                    </button>
                </div>
            `;
        }

        function resetForm() {
            form.reset();
            form.style.display = 'block';
            result.style.display = 'none';
            fileInfo.style.display = 'none';
        }

        async function checkStatus() {
            const importId = document.getElementById('importId').value;
            const statusResult = document.getElementById('statusResult');

            if (!importId) {
                showStatusResult('warning', 'Digite um ID válido para consultar');
                return;
            }

            try {
                const response = await fetch(`/api/freight/import/${importId}/status`);
                const data = await response.json();

                if (data.success) {
                    const status = data.data.status;
                    const statusText = getStatusText(status);
                    const statusClass = getStatusClass(status);

                    let message = `
                        <strong>Cliente:</strong> ${data.data.client.name}<br>
                        <strong>Arquivo:</strong> ${data.data.file_name}<br>
                        <strong>Total de registros:</strong> ${data.data.total_rows}<br>
                        <strong>Erros:</strong> ${data.data.total_errors}<br>
                        <strong>Progresso:</strong> ${data.data.progress_percentage}%
                    `;

                    showStatusResult(statusClass, `Status: ${statusText}`, message);
                } else {
                    showStatusResult('danger', 'Importação não encontrada');
                }
            } catch (error) {
                showStatusResult('danger', 'Erro ao consultar status');
            }
        }

        function getStatusText(status) {
            const statusMap = {
                'pending': 'Pendente',
                'processing': 'Processando',
                'completed': 'Concluída',
                'failed': 'Falhou'
            };
            return statusMap[status] || status;
        }

        function getStatusClass(status) {
            const classMap = {
                'pending': 'warning',
                'processing': 'info',
                'completed': 'success',
                'failed': 'danger'
            };
            return classMap[status] || 'secondary';
        }

        function showStatusResult(type, title, message = '') {
            const statusResult = document.getElementById('statusResult');
            statusResult.innerHTML = `
                <div class="alert alert-${type}" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'info' ? 'info-circle' : 'exclamation-triangle'}"></i>
                        ${title}
                    </h6>
                    ${message ? `<div class="mt-2">${message}</div>` : ''}
                </div>
            `;
            statusResult.style.display = 'block';
        }

        async function loadImports() {
            const loading = document.getElementById('importsLoading');
            const table = document.getElementById('importsTable');

            try {
                loading.style.display = 'block';
                table.innerHTML = '';

                const response = await fetch('/api/freight/imports');
                const result = await response.json();

                if (result.success && result.data) {
                    renderImportsTable(result.data);
                } else {
                    table.innerHTML = '<div class="alert alert-warning">Nenhuma importação encontrada.</div>';
                }
            } catch (error) {
                table.innerHTML = '<div class="alert alert-danger">Erro ao carregar importações.</div>';
            } finally {
                loading.style.display = 'none';
            }
        }

        function renderImportsTable(imports) {
            const table = document.getElementById('importsTable');

            if (imports.length === 0) {
                table.innerHTML = '<div class="alert alert-info">Nenhuma importação realizada ainda.</div>';
                return;
            }

            let tableHTML = `
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Arquivo</th>
                            <th>Status</th>
                            <th>Registros</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            imports.forEach(importItem => {
                const statusClass = getStatusClass(importItem.status);
                const statusText = getStatusText(importItem.status);
                const date = new Date(importItem.created_at).toLocaleString('pt-BR');

                tableHTML += `
                    <tr>
                        <td><strong>#${importItem.id}</strong></td>
                        <td>
                            <div>${importItem.client.name}</div>
                            <small class="text-muted">${importItem.client.document}</small>
                        </td>
                        <td>
                            <i class="fas fa-file-csv text-success"></i>
                            ${importItem.file_name}
                        </td>
                        <td>
                            <span class="badge bg-${statusClass}">${statusText}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">${importItem.total_rows || 0}</span>
                        </td>
                        <td>
                            <small>${date}</small>
                        </td>
                    </tr>
                `;
            });

            tableHTML += `
                    </tbody>
                </table>
            `;

            table.innerHTML = tableHTML;
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadImports();
        });
    </script>
</body>

</html>