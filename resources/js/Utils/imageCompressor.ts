/**
 * Utilitário para redimensionar e comprimir imagens no cliente usando Canvas HTML5 nativo.
 * Garante que imagens pesadas de celular sejam otimizadas para tamanhos leves (< 300KB)
 * mantendo a proporção e resolução ideais para logotipos em telas e PDFs.
 */

export interface ImageOptimizationOptions {
    maxWidth?: number;
    maxHeight?: number;
    quality?: number;
    maxFileSizeBytes?: number; // Limite bruto de segurança inicial (ex: 20MB)
}

export interface OptimizedImageResult {
    file: File;
    previewUrl: string;
    originalSizeBytes: number;
    optimizedSizeBytes: number;
}

export function optimizeImage(
    file: File,
    options: ImageOptimizationOptions = {}
): Promise<OptimizedImageResult> {
    const {
        maxWidth = 800,
        maxHeight = 800,
        quality = 0.85,
        maxFileSizeBytes = 20 * 1024 * 1024, // 20MB limite máximo absoluto de arquivo suportado para leitura
    } = options;

    return new Promise((resolve, reject) => {
        // Validação 1: Tipo MIME deve ser imagem
        if (!file.type.startsWith('image/')) {
            return reject(new Error('O arquivo selecionado não é uma imagem válida.'));
        }

        // Validação 2: Limite máximo inicial (evitar travar navegador com arquivos gigantes de >20MB)
        if (file.size > maxFileSizeBytes) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
            return reject(new Error(`O arquivo original é excessivamente grande (${sizeMB}MB). Escolha um arquivo menor que 20MB.`));
        }

        const reader = new FileReader();

        reader.onload = (readerEvent) => {
            const img = new Image();

            img.onload = () => {
                let { width, height } = img;

                // Calcula as novas dimensões mantendo o aspect ratio
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                // Cria o elemento canvas em memória
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return reject(new Error('Não foi possível inicializar o processador de imagem do navegador.'));
                }

                // Suavização para alta qualidade de renderização
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';

                // Desenha a imagem redimensionada no canvas
                ctx.drawImage(img, 0, 0, width, height);

                // Determina o formato de saída (preserva PNG para transparência se aplicável, ou JPEG para compressão máxima)
                const outputMimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';

                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            return reject(new Error('Falha ao processar e comprimir a imagem.'));
                        }

                        // Garante que o arquivo otimizado não ultrapasse 2MB
                        const absoluteMaxOutput = 2 * 1024 * 1024;
                        if (blob.size > absoluteMaxOutput) {
                            return reject(new Error('Mesmo após a otimização, a imagem excedeu o limite de 2MB. Escolha uma imagem com menor complexidade.'));
                        }

                        // Cria o novo objeto File resultante
                        const extension = outputMimeType === 'image/png' ? 'png' : 'jpg';
                        const baseName = file.name.replace(/\.[^/.]+$/, '');
                        const optimizedFile = new File([blob], `${baseName}-otimizado.${extension}`, {
                            type: outputMimeType,
                            lastModified: Date.now(),
                        });

                        resolve({
                            file: optimizedFile,
                            previewUrl: URL.createObjectURL(blob),
                            originalSizeBytes: file.size,
                            optimizedSizeBytes: blob.size,
                        });
                    },
                    outputMimeType,
                    quality
                );
            };

            img.onerror = () => {
                reject(new Error('Não foi possível ler a imagem selecionada. O arquivo pode estar corrompido.'));
            };

            img.src = readerEvent.target?.result as string;
        };

        reader.onerror = () => {
            reject(new Error('Erro ao ler os dados do arquivo no seu dispositivo.'));
        };

        reader.readAsDataURL(file);
    });
}
