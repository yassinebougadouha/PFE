import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { visualAiApi } from '@/shared/api/visual-ai';
import { normalizeError } from '@/shared/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { VisualAiScreenshareTab } from '@/features/visual-ai/VisualAiScreenshareTab';
import { VisualAiTroubleshootingWizardTab } from '@/features/visual-ai/VisualAiTroubleshootingWizardTab';
import { useToast } from '@/hooks/use-toast';
import { useAuth } from '@/features/auth/AuthContext';
import { Upload, Loader2, Trash2, Eye, AlertCircle } from 'lucide-react';
import type { ScreenshotAnalysis, ReferenceScreen } from '@/shared/types';

export function VisualAiPage() {
  const { user } = useAuth();
  const { toast } = useToast();
  const qc = useQueryClient();
  const [searchParams] = useSearchParams();
  const [file, setFile] = useState<File | null>(null);
  const [refKey, setRefKey] = useState('');
  const [analysis, setAnalysis] = useState<ScreenshotAnalysis | null>(null);

  const requestedTab = searchParams.get('tab');
  const availableTabs = user?.role === 'admin'
    ? ['analyze', 'gap', 'screenshare', 'wizard', 'references']
    : ['analyze', 'gap', 'screenshare', 'wizard'];
  const defaultTab = requestedTab && availableTabs.includes(requestedTab) ? requestedTab : 'analyze';

  const analyzeMut = useMutation({
    mutationFn: () => visualAiApi.analyzeScreenshot(file!),
    onSuccess: setAnalysis,
    onError: (err) => toast({ variant: 'destructive', description: normalizeError(err) }),
  });

  const gapMut = useMutation({
    mutationFn: () => visualAiApi.gapDetect(file!, refKey),
    onSuccess: setAnalysis,
    onError: (err) => toast({ variant: 'destructive', description: normalizeError(err) }),
  });

  const { data: refs, isLoading: refsLoading } = useQuery({ queryKey: ['visual-ai-refs'], queryFn: visualAiApi.references.list });
  const deleteMut = useMutation({
    mutationFn: visualAiApi.references.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['visual-ai-refs'] }),
    onError: (err) => toast({ variant: 'destructive', description: normalizeError(err) }),
  });

  return (
    <div className="p-6 space-y-6 max-w-5xl mx-auto">
      <h1 className="text-2xl font-bold tracking-tight">Visual AI</h1>

      <Tabs defaultValue={defaultTab}>
        <TabsList>
          <TabsTrigger value="analyze">Screenshot Analysis</TabsTrigger>
          <TabsTrigger value="gap">Gap Detection</TabsTrigger>
          <TabsTrigger value="screenshare">Screenshare Assistance</TabsTrigger>
          <TabsTrigger value="wizard">Troubleshooting Wizard</TabsTrigger>
          {user?.role === 'admin' && <TabsTrigger value="references">References</TabsTrigger>}
        </TabsList>

        <TabsContent value="analyze" className="mt-4 space-y-4">
          <div className="rounded-xl border bg-card p-4 space-y-3">
            <Label>Upload Screenshot</Label>
            <Input type="file" accept="image/*" onChange={e => setFile(e.target.files?.[0] || null)} />
            <Button size="sm" disabled={!file || analyzeMut.isPending} onClick={() => analyzeMut.mutate()}>
              {analyzeMut.isPending ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : <Upload className="h-4 w-4 mr-1" />}Analyze
            </Button>
          </div>
          {analysis && <AnalysisResult analysis={analysis} />}
        </TabsContent>

        <TabsContent value="gap" className="mt-4 space-y-4">
          <div className="rounded-xl border bg-card p-4 space-y-3">
            <Label>Upload Screenshot</Label>
            <Input type="file" accept="image/*" onChange={e => setFile(e.target.files?.[0] || null)} />
            <Label>Reference Key</Label>
            <Input value={refKey} onChange={e => setRefKey(e.target.value)} placeholder="reference-screen-key" />
            <Button size="sm" disabled={!file || !refKey || gapMut.isPending} onClick={() => gapMut.mutate()}>
              {gapMut.isPending ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : <Eye className="h-4 w-4 mr-1" />}Detect Gaps
            </Button>
          </div>
          {analysis && <AnalysisResult analysis={analysis} />}
        </TabsContent>

        <TabsContent value="screenshare" className="mt-4">
          <VisualAiScreenshareTab />
        </TabsContent>

        <TabsContent value="wizard" className="mt-4">
          <VisualAiTroubleshootingWizardTab />
        </TabsContent>

        {user?.role === 'admin' && (
          <TabsContent value="references" className="mt-4">
            <div className="rounded-xl border bg-card overflow-hidden">
              {refsLoading ? <div className="p-4 text-sm text-muted-foreground">Loading...</div> : !refs?.length ? (
                <div className="p-8 text-center text-sm text-muted-foreground">No reference screens</div>
              ) : (
                <div className="divide-y">
                  {refs.map((r: ReferenceScreen) => (
                    <div key={r.id} className="flex items-center justify-between p-4">
                      <div>
                        <p className="text-sm font-medium">{r.name}</p>
                        <p className="text-xs text-muted-foreground">Key: {r.key} · {new Date(r.created_at).toLocaleDateString()}</p>
                      </div>
                      <Button variant="ghost" size="icon" onClick={() => deleteMut.mutate(r.id)}><Trash2 className="h-4 w-4 text-destructive" /></Button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}

function AnalysisResult({ analysis }: { analysis: ScreenshotAnalysis }) {
  return (
    <div className="rounded-xl border bg-card p-4 space-y-3">
      <h3 className="text-sm font-semibold">Analysis Result</h3>
      <p className="text-sm">{analysis.caption}</p>
      {analysis.ocr_text && <div><p className="text-xs font-medium text-muted-foreground">OCR Text</p><p className="text-sm bg-muted rounded p-2 mt-1 font-mono text-xs">{analysis.ocr_text}</p></div>}
      <div className="flex flex-wrap gap-1">
        {analysis.labels.map((l, i) => <span key={i} className="text-xs bg-accent rounded-full px-2 py-0.5">{l}</span>)}
      </div>
      <p className="text-xs text-muted-foreground">Elements detected: {analysis.elements}</p>
      {analysis.guidance.length > 0 && (
        <div>
          <p className="text-xs font-medium mb-1">Guidance</p>
          <ul className="space-y-1">{analysis.guidance.map((g, i) => <li key={i} className="text-sm flex gap-2"><AlertCircle className="h-3 w-3 mt-1 text-info shrink-0" />{g}</li>)}</ul>
        </div>
      )}
      {analysis.gaps && analysis.gaps.length > 0 && (
        <div>
          <p className="text-xs font-medium mb-1 text-destructive">Gaps Detected</p>
          <ul className="space-y-1">{analysis.gaps.map((g, i) => <li key={i} className="text-sm text-destructive">{g}</li>)}</ul>
        </div>
      )}
    </div>
  );
}
