<template>
    <Head title="Algorithm Diagnostics Matrix" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/admin/booking-analytics" class="text-indigo-400 hover:text-indigo-600 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                    Algorithm Diagnostics Matrix
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- Evaluation Matrix -->
                <div class="bg-slate-900 rounded-2xl shadow-2xl border border-slate-700 overflow-hidden animate-fade-in-down">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            1. Evaluation Matrix
                        </h3>
                    </div>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- RF Evaluation -->
                        <div>
                            <h4 class="text-indigo-300 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest (Safety Predictor)</h4>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4">
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Accuracy</div>
                                    <div class="text-3xl font-black text-emerald-400">{{ (diagnostics.evaluation.random_forest.accuracy * 100).toFixed(1) }}%</div>
                                </div>
                                <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4">
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">F1-Score</div>
                                    <div class="text-3xl font-black text-cyan-400">{{ diagnostics.evaluation.random_forest.f1_score.toFixed(2) }}</div>
                                </div>
                                <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4">
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Precision</div>
                                    <div class="text-3xl font-black text-blue-400">{{ diagnostics.evaluation.random_forest.precision.toFixed(2) }}</div>
                                </div>
                                <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4">
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Recall</div>
                                    <div class="text-3xl font-black text-purple-400">{{ diagnostics.evaluation.random_forest.recall.toFixed(2) }}</div>
                                </div>
                            </div>

                            <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                <div class="text-sm font-semibold text-slate-300 mb-3">Confusion Matrix</div>
                                <div class="grid grid-cols-3 gap-2 text-center text-sm items-center">
                                    <div class="col-start-2 text-[10px] uppercase text-slate-400 font-bold">Pred: Operational</div>
                                    <div class="col-start-3 text-[10px] uppercase text-slate-400 font-bold">Pred: Cancelled</div>
                                    
                                    <div class="text-[10px] uppercase text-slate-400 font-bold text-right pr-2">Act: Operational</div>
                                    <div class="bg-emerald-500/20 border border-emerald-500/30 rounded p-3 text-emerald-300 font-mono text-lg font-bold">{{ diagnostics.evaluation.random_forest.confusion_matrix[0].predicted_operational }}</div>
                                    <div class="bg-rose-500/20 border border-rose-500/30 rounded p-3 text-rose-300 font-mono text-lg">{{ diagnostics.evaluation.random_forest.confusion_matrix[0].predicted_cancelled }}</div>
                                    
                                    <div class="text-[10px] uppercase text-slate-400 font-bold text-right pr-2">Act: Cancelled</div>
                                    <div class="bg-rose-500/20 border border-rose-500/30 rounded p-3 text-rose-300 font-mono text-lg">{{ diagnostics.evaluation.random_forest.confusion_matrix[1].predicted_operational }}</div>
                                    <div class="bg-slate-700 border border-slate-600 rounded p-3 text-slate-300 font-mono text-lg font-bold">{{ diagnostics.evaluation.random_forest.confusion_matrix[1].predicted_cancelled }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- TOPSIS Evaluation -->
                        <div>
                            <h4 class="text-indigo-300 font-semibold mb-4 text-sm uppercase tracking-wider">TOPSIS (Recommendation Engine)</h4>
                            <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-6 h-full flex flex-col justify-center space-y-6">
                                <div>
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Decision Criteria</div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="criterion in diagnostics.evaluation.topsis.criteria" :key="criterion" class="bg-indigo-500/20 text-indigo-300 px-3 py-1 rounded text-sm font-medium border border-indigo-500/30">
                                            {{ criterion }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Matrix Type</div>
                                    <div class="text-lg font-medium text-slate-200">{{ diagnostics.evaluation.topsis.type }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Normalization Method</div>
                                    <div class="text-lg font-medium text-slate-200 font-mono">{{ diagnostics.evaluation.topsis.normalization_method }}</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Hyperparameter Matrix -->
                <div class="bg-slate-900 rounded-2xl shadow-2xl border border-slate-700 overflow-hidden animate-fade-in-down" style="animation-delay: 0.1s">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            2. Hyperparameter Matrix
                        </h3>
                    </div>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- RF Hyperparameters -->
                        <div>
                            <h4 class="text-emerald-300 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest Configuration</h4>
                            <div class="bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                                <table class="w-full text-sm text-left">
                                    <tbody class="divide-y divide-slate-700">
                                        <tr v-for="(value, key) in diagnostics.hyperparameters.random_forest" :key="key" class="hover:bg-slate-800">
                                            <td class="px-4 py-3 font-mono text-slate-400">{{ key }}</td>
                                            <td class="px-4 py-3 font-mono text-emerald-400 text-right">{{ value }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TOPSIS Hyperparameters -->
                        <div>
                            <h4 class="text-emerald-300 font-semibold mb-4 text-sm uppercase tracking-wider">TOPSIS Preference Weights</h4>
                            <div class="space-y-4">
                                <div v-for="(weights, pref) in diagnostics.hyperparameters.topsis_weights" :key="pref" class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
                                    <div class="text-xs text-emerald-400 uppercase tracking-widest font-bold mb-3 border-b border-slate-700 pb-2">{{ pref }}</div>
                                    <div class="grid grid-cols-4 gap-2 text-center">
                                        <div v-for="(w, c) in weights" :key="c">
                                            <div class="text-[10px] text-slate-400 uppercase">{{ c }}</div>
                                            <div class="font-mono text-white mt-1">{{ w.toFixed(2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Algorithmic Execution Pipeline -->
                <div class="bg-slate-900 rounded-2xl shadow-2xl border border-slate-700 overflow-hidden animate-fade-in-down" style="animation-delay: 0.2s">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            3. Algorithmic Execution Pipeline
                        </h3>
                    </div>
                    <div class="p-8">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-400 uppercase bg-slate-800 border-b border-slate-700">
                                    <tr>
                                        <th class="px-6 py-4">Stage</th>
                                        <th class="px-6 py-4">Algorithm / Function</th>
                                        <th class="px-6 py-4 text-right">Execution Time</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <tr v-for="exec in diagnostics.execution_pipeline" :key="exec.stage" class="hover:bg-slate-800/50 transition">
                                        <td class="px-6 py-4 font-bold text-slate-200">{{ exec.stage }}</td>
                                        <td class="px-6 py-4 font-mono text-cyan-300">{{ exec.algorithm }}</td>
                                        <td class="px-6 py-4 text-right font-mono text-slate-400">{{ exec.time_ms }} ms</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 rounded text-[10px] font-bold uppercase tracking-wider"
                                                  :class="exec.status.includes('Optimal') ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'">
                                                {{ exec.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Random Forest Branches -->
                        <div class="mt-8">
                            <h4 class="text-cyan-300 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest Decision Branches (All Nodes)</h4>
                            <div class="max-h-96 overflow-y-auto custom-scrollbar pr-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="branch in diagnostics.rf_branches" :key="branch.branch_id" class="bg-slate-800 border border-slate-700 rounded-xl p-5 hover:border-cyan-500/50 transition duration-300">
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-xs font-bold text-cyan-400 font-mono">{{ branch.branch_id }}</span>
                                            <span class="text-[10px] uppercase font-bold px-2 py-1 rounded-full tracking-wider"
                                                  :class="branch.prediction === 'Operational' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'">
                                                {{ branch.prediction }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-slate-200 font-mono mb-4 bg-slate-900 p-3 rounded border border-slate-700/50 break-words">
                                            <span class="text-slate-500">IF</span> {{ branch.rule }}
                                        </div>
                                        <div class="flex justify-between items-center text-xs text-slate-400">
                                            <span>Gini: <span class="text-white">{{ branch.gini }}</span></span>
                                            <span>Samples: <span class="text-white">{{ branch.samples.toLocaleString() }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Mathematical Foundations -->
                <div class="bg-slate-900 rounded-2xl shadow-2xl border border-slate-700 overflow-hidden animate-fade-in-down" style="animation-delay: 0.3s">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6 text-fuchsia-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            4. Mathematical Foundations
                        </h3>
                    </div>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Random Forest Math -->
                        <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-6">
                            <h4 class="text-fuchsia-300 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest Splitting Criteria</h4>
                            
                            <div class="mb-6">
                                <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Gini Impurity Formula</div>
                                <div class="bg-slate-900 border border-slate-700 rounded p-4 flex justify-center items-center font-serif text-2xl text-slate-200 tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i>Gini</i> = 1 - 
                                        <div class="flex flex-col justify-center items-center relative mx-1">
                                            <span class="text-[10px] leading-none text-slate-400"><i>c</i></span>
                                            <span class="text-4xl leading-none">&sum;</span>
                                            <span class="text-[10px] leading-none text-slate-400"><i>i=1</i></span>
                                        </div>
                                        (<i>p<sub>i</sub></i>)<sup>2</sup>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-2 text-center">Where <i>c</i> is total number of classes, and <i>p<sub>i</sub></i> is probability/proportion of class <i>i</i>.</p>
                            </div>

                            <div>
                                <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Information Entropy Formula</div>
                                <div class="bg-slate-900 border border-slate-700 rounded p-4 flex justify-center items-center font-serif text-2xl text-slate-200 tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i>Entropy</i> = - 
                                        <div class="flex flex-col justify-center items-center relative mx-1">
                                            <span class="text-[10px] leading-none text-slate-400"><i>c</i></span>
                                            <span class="text-4xl leading-none">&sum;</span>
                                            <span class="text-[10px] leading-none text-slate-400"><i>i=1</i></span>
                                        </div>
                                        <i>p<sub>i</sub></i> log<sub>2</sub>(<i>p<sub>i</sub></i>)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOPSIS Math -->
                        <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-6">
                            <h4 class="text-fuchsia-300 font-semibold mb-4 text-sm uppercase tracking-wider">TOPSIS Ranking Distance</h4>
                            
                            <div class="mb-6">
                                <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Euclidean Distance to Ideal Best</div>
                                <div class="bg-slate-900 border border-slate-700 rounded p-4 flex justify-center items-center font-serif text-2xl text-slate-200 tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i>D<sub>i</sub><sup>*</sup></i> = 
                                        <span class="text-4xl font-light">&radic;</span>
                                        <div class="border-t border-slate-200 pt-1 -ml-1 flex items-center gap-2"> 
                                            <div class="flex flex-col justify-center items-center relative mx-1">
                                                <span class="text-[10px] leading-none text-slate-400"><i>n</i></span>
                                                <span class="text-3xl leading-none">&sum;</span>
                                                <span class="text-[10px] leading-none text-slate-400"><i>j=1</i></span>
                                            </div>
                                            (<i>v<sub>ij</sub></i> - <i>v<sub>j</sub><sup>*</sup></i>)<sup>2</sup> 
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-2 text-center">Where <i>v<sub>ij</sub></i> is weighted normalized value, and <i>v<sub>j</sub><sup>*</sup></i> is ideal best value.</p>
                            </div>

                            <div>
                                <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Performance Score (Ranking)</div>
                                <div class="bg-slate-900 border border-slate-700 rounded p-4 flex justify-center items-center font-serif text-2xl text-slate-200 tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <i>S<sub>i</sub></i> = 
                                        <div class="inline-flex flex-col items-center">
                                            <span class="border-b border-slate-200 px-2 pb-1"><i>D<sub>i</sub><sup>-</sup></i></span>
                                            <span class="pt-1"><i>D<sub>i</sub><sup>*</sup></i> + <i>D<sub>i</sub><sup>-</sup></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    diagnostics: Object
})
</script>

<style>
@keyframes fadeInDown {
    from { opacity: 0; transform: translate3d(0, -20px, 0); }
    to { opacity: 1; transform: translate3d(0, 0, 0); }
}
.animate-fade-in-down { animation: fadeInDown 0.6s ease-out both; }

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.5); 
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(6, 182, 212, 0.5); /* cyan-500 */
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(6, 182, 212, 0.8); 
}
</style>
