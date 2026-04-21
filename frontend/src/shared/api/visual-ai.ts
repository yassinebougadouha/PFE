import { apiClient } from './client';
import type {
  ScreenshotAnalysis,
  ReferenceScreen,
  ScreenshareRealtimeChunkResult,
  ScreenshareResult,
  TroubleshootingWizardRequest,
  TroubleshootingWizardResponse,
} from '@/shared/types';

export const visualAiApi = {
  analyzeScreenshot: (file: File) => {
    const fd = new FormData();
    fd.append('file', file);
    return apiClient<ScreenshotAnalysis>('/visual-ai/screenshot/analyze', { method: 'POST', body: fd });
  },
  gapDetect: (file: File, referenceKey: string) => {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('reference_key', referenceKey);
    return apiClient<ScreenshotAnalysis>('/visual-ai/screenshot/gap-detect', { method: 'POST', body: fd });
  },
  references: {
    list: () => apiClient<ReferenceScreen[]>('/visual-ai/references'),
    get: (id: string) => apiClient<ReferenceScreen>(`/visual-ai/references/${id}`),
    create: (data: FormData) => apiClient<ReferenceScreen>('/visual-ai/references', { method: 'POST', body: data }),
    delete: (id: string) => apiClient<void>(`/visual-ai/references/${id}`, { method: 'DELETE' }),
  },
  timeline: (conversationId: string) => apiClient<ScreenshotAnalysis[]>(`/visual-ai/timeline/${conversationId}`),
  screenshareFrames: (files: File[], opts: Record<string, any>) => {
    const fd = new FormData();
    files.forEach(f => fd.append('frames', f));
    Object.entries(opts).forEach(([k, v]) => { if (v !== undefined) fd.append(k, String(v)); });
    return apiClient<ScreenshareResult>('/visual-ai/screenshare/assist', { method: 'POST', body: fd });
  },
  screenshareVideo: (video: File, opts: Record<string, any>) => {
    const fd = new FormData();
    fd.append('file', video);
    fd.append('video', video);
    Object.entries(opts).forEach(([k, v]) => { if (v !== undefined) fd.append(k, String(v)); });
    return apiClient<ScreenshareResult>('/visual-ai/screenshare/assist-video', { method: 'POST', body: fd });
  },
  screenshareRealtimeChunk: (videoChunk: Blob | File, opts: Record<string, any>) => {
    const fd = new FormData();
    const file =
      videoChunk instanceof File
        ? videoChunk
        : new File([videoChunk], `screenshare-chunk-${Date.now()}.webm`, {
            type: videoChunk.type || 'video/webm',
          });

    fd.append('file', file);
    fd.append('video', file);
    Object.entries(opts).forEach(([k, v]) => {
      if (v !== undefined) {
        fd.append(k, String(v));
      }
    });

    return apiClient<ScreenshareRealtimeChunkResult>('/visual-ai/screenshare/assist-realtime-chunk', {
      method: 'POST',
      body: fd,
    });
  },
  troubleshootingWizard: (payload: TroubleshootingWizardRequest) =>
    apiClient<TroubleshootingWizardResponse>('/visual-ai/troubleshooting/wizard', {
      method: 'POST',
      body: JSON.stringify(payload),
    }),
};
