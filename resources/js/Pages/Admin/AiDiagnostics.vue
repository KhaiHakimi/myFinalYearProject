<template>
    <Head title="Algorithm Diagnostics Matrix" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/admin/booking-analytics" class="text-indigo-600 hover:text-indigo-800 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-2xl font-extrabold leading-tight text-gray-900">
                    Algorithm Diagnostics Matrix
                </h2>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- Evaluation Matrix -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            1. Evaluation Matrix (Accuracy Testing)
                        </h3>
                    </div>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- RF Evaluation -->
                        <div>
                            <h4 class="text-indigo-600 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest (Safety Predictor)</h4>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Accuracy</div>
                                    <div class="text-3xl font-black text-emerald-600">{{ (diagnostics.evaluation.random_forest.accuracy * 100).toFixed(1) }}%</div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">F1-Score</div>
                                    <div class="text-3xl font-black text-cyan-600">{{ diagnostics.evaluation.random_forest.f1_score.toFixed(2) }}</div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Precision</div>
                                    <div class="text-3xl font-black text-blue-600">{{ diagnostics.evaluation.random_forest.precision.toFixed(2) }}</div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Recall</div>
                                    <div class="text-3xl font-black text-purple-600">{{ diagnostics.evaluation.random_forest.recall.toFixed(2) }}</div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                                <div class="text-sm font-semibold text-gray-700 mb-3">Confusion Matrix</div>
                                <div class="grid grid-cols-3 gap-2 text-center text-sm items-center">
                                    <div class="col-start-2 text-[10px] uppercase text-gray-500 font-bold">Pred: Operational</div>
                                    <div class="col-start-3 text-[10px] uppercase text-gray-500 font-bold">Pred: Cancelled</div>
                                    
                                    <div class="text-[10px] uppercase text-gray-500 font-bold text-right pr-2">Act: Operational</div>
                                    <div class="bg-emerald-100 border border-emerald-200 rounded p-3 text-emerald-700 font-mono text-lg font-bold">{{ diagnostics.evaluation.random_forest.confusion_matrix[0].predicted_operational }}</div>
                                    <div class="bg-rose-50 border border-rose-200 rounded p-3 text-rose-600 font-mono text-lg">{{ diagnostics.evaluation.random_forest.confusion_matrix[0].predicted_cancelled }}</div>
                                    
                                    <div class="text-[10px] uppercase text-gray-500 font-bold text-right pr-2">Act: Cancelled</div>
                                    <div class="bg-rose-50 border border-rose-200 rounded p-3 text-rose-600 font-mono text-lg">{{ diagnostics.evaluation.random_forest.confusion_matrix[1].predicted_operational }}</div>
                                    <div class="bg-gray-100 border border-gray-300 rounded p-3 text-gray-700 font-mono text-lg font-bold">{{ diagnostics.evaluation.random_forest.confusion_matrix[1].predicted_cancelled }}</div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mt-6">
                                <div class="text-sm font-semibold text-gray-700 mb-3">Historical Accuracy Tracking</div>
                                <div class="h-48 relative">
                                    <Line :data="rfChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>

                        <!-- TOPSIS Evaluation -->
                        <div>
                            <h4 class="text-indigo-600 font-semibold mb-4 text-sm uppercase tracking-wider">TOPSIS (Recommendation Engine)</h4>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 h-full flex flex-col justify-center space-y-6">
                                <div>
                                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Decision Criteria</div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="criterion in diagnostics.evaluation.topsis.criteria" :key="criterion" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-sm font-medium border border-indigo-200">
                                            {{ criterion }}
                                        </span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Matrix Type</div>
                                        <div class="text-sm font-medium text-gray-800">{{ diagnostics.evaluation.topsis.type }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Normalization</div>
                                        <div class="text-sm font-medium text-gray-800 font-mono">{{ diagnostics.evaluation.topsis.normalization_method }}</div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4">
                                    <h5 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Ranking Accuracy Metrics</h5>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                                            <div class="text-[10px] text-gray-500 uppercase">Spearman Correlation</div>
                                            <div class="text-xl font-bold text-emerald-600 mt-1">{{ diagnostics.evaluation.topsis.spearman_correlation.toFixed(2) }}</div>
                                        </div>
                                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                                            <div class="text-[10px] text-gray-500 uppercase">Consistency Ratio</div>
                                            <div class="text-xl font-bold text-blue-600 mt-1">{{ diagnostics.evaluation.topsis.consistency_ratio.toFixed(3) }}</div>
                                        </div>
                                        <div class="bg-white border border-gray-200 rounded-lg p-3 col-span-2 flex justify-between items-center">
                                            <div class="text-[10px] text-gray-500 uppercase">Mean Closeness Coefficient (Satisfaction)</div>
                                            <div class="text-xl font-bold text-purple-600">{{ (diagnostics.evaluation.topsis.mean_closeness_coefficient * 100).toFixed(1) }}%</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4">
                                    <h5 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Historical Ranking Correlation</h5>
                                    <div class="bg-white border border-gray-200 rounded-lg p-3 h-48 relative">
                                        <Line :data="topsisChartData" :options="chartOptions" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Hyperparameter Matrix -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down" style="animation-delay: 0.1s">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            2. Hyperparameter Matrix
                        </h3>
                    </div>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- RF Hyperparameters -->
                        <div>
                            <h4 class="text-emerald-600 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest Configuration</h4>
                            <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                <table class="w-full text-sm text-left">
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="(value, key) in diagnostics.hyperparameters.random_forest" :key="key" class="hover:bg-gray-100">
                                            <td class="px-4 py-3 font-mono text-gray-600">{{ key }}</td>
                                            <td class="px-4 py-3 font-mono text-emerald-600 text-right font-bold">{{ value }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TOPSIS Hyperparameters -->
                        <div>
                            <h4 class="text-emerald-600 font-semibold mb-4 text-sm uppercase tracking-wider">TOPSIS Preference Weights</h4>
                            <div class="space-y-4">
                                <div v-for="(weights, pref) in diagnostics.hyperparameters.topsis_weights" :key="pref" class="bg-gray-50 rounded-xl border border-gray-200 p-4 shadow-sm">
                                    <div class="text-xs text-emerald-700 uppercase tracking-widest font-bold mb-3 border-b border-gray-200 pb-2">{{ pref }}</div>
                                    <div class="grid grid-cols-4 gap-2 text-center">
                                        <div v-for="(w, c) in weights" :key="c">
                                            <div class="text-[10px] text-gray-500 uppercase">{{ c }}</div>
                                            <div class="font-mono text-gray-800 font-bold mt-1">{{ w.toFixed(2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Algorithmic Execution Pipeline -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down" style="animation-delay: 0.2s">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                            <svg class="w-6 h-6 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            3. Algorithmic Execution Pipeline
                        </h3>
                    </div>
                    <div class="p-8">
                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4">Stage</th>
                                        <th class="px-6 py-4">Algorithm / Function</th>
                                        <th class="px-6 py-4 text-right">Execution Time</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="exec in diagnostics.execution_pipeline" :key="exec.stage" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-bold text-gray-800">{{ exec.stage }}</td>
                                        <td class="px-6 py-4 font-mono text-cyan-700">{{ exec.algorithm }}</td>
                                        <td class="px-6 py-4 text-right font-mono text-gray-500">{{ exec.time_ms }} ms</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 rounded text-[10px] font-bold uppercase tracking-wider"
                                                  :class="exec.status.includes('Optimal') ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-yellow-100 text-yellow-800 border border-yellow-200'">
                                                {{ exec.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Random Forest Branches -->
                        <div class="mt-8">
                            <h4 class="text-cyan-600 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest Decision Branches (All Nodes with Accuracy)</h4>
                            <div class="max-h-96 overflow-y-auto custom-scrollbar-light pr-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="branch in diagnostics.rf_branches" :key="branch.branch_id" class="bg-gray-50 border border-gray-200 rounded-xl p-5 hover:border-cyan-300 transition duration-300 shadow-sm relative">
                                        
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-xs font-bold text-cyan-600 font-mono">{{ branch.branch_id }}</span>
                                            <span class="text-[10px] uppercase font-bold px-2 py-1 rounded-full tracking-wider"
                                                  :class="branch.prediction === 'Operational' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                                {{ branch.prediction }}
                                            </span>
                                        </div>
                                        
                                        <div class="text-sm text-gray-800 font-mono mb-4 bg-white p-3 rounded border border-gray-200 break-words shadow-inner">
                                            <span class="text-gray-400 font-sans font-bold text-xs">IF</span> {{ branch.rule }}
                                        </div>
                                        
                                        <!-- Test Results -->
                                        <div class="bg-white border border-gray-200 rounded p-2 mb-3">
                                            <div class="flex justify-between text-[10px] text-gray-500 mb-1">
                                                <span>Node Accuracy</span>
                                                <span class="font-bold text-emerald-600">{{ branch.accuracy }}</span>
                                            </div>
                                            <div class="flex justify-between text-[10px] text-gray-500">
                                                <span>Node Purity</span>
                                                <span class="font-bold text-blue-600">{{ branch.purity }}</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between items-center text-xs text-gray-500 border-t border-gray-200 pt-2">
                                            <span>Gini: <span class="font-bold text-gray-800">{{ branch.gini }}</span></span>
                                            <span>Samples: <span class="font-bold text-gray-800">{{ branch.samples.toLocaleString() }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Mathematical Foundations -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down" style="animation-delay: 0.3s">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                            <svg class="w-6 h-6 text-fuchsia-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            4. Mathematical Foundations
                        </h3>
                    </div>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Random Forest Math -->
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 shadow-sm">
                            <h4 class="text-fuchsia-600 font-semibold mb-4 text-sm uppercase tracking-wider">Random Forest Splitting Criteria</h4>
                            
                            <div class="mb-6">
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Gini Impurity Formula</div>
                                <div class="bg-white border border-gray-200 rounded p-4 flex justify-center items-center font-serif text-2xl text-gray-800 tracking-wider shadow-inner">
                                    <div class="flex items-center gap-2">
                                        <i>Gini</i> = 1 - 
                                        <div class="flex flex-col justify-center items-center relative mx-1">
                                            <span class="text-[10px] leading-none text-gray-400"><i>c</i></span>
                                            <span class="text-4xl leading-none">&sum;</span>
                                            <span class="text-[10px] leading-none text-gray-400"><i>i=1</i></span>
                                        </div>
                                        (<i>p<sub>i</sub></i>)<sup>2</sup>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-2 text-center">Where <i>c</i> is total number of classes, and <i>p<sub>i</sub></i> is probability/proportion of class <i>i</i>.</p>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Information Entropy Formula</div>
                                <div class="bg-white border border-gray-200 rounded p-4 flex justify-center items-center font-serif text-2xl text-gray-800 tracking-wider shadow-inner">
                                    <div class="flex items-center gap-2">
                                        <i>Entropy</i> = - 
                                        <div class="flex flex-col justify-center items-center relative mx-1">
                                            <span class="text-[10px] leading-none text-gray-400"><i>c</i></span>
                                            <span class="text-4xl leading-none">&sum;</span>
                                            <span class="text-[10px] leading-none text-gray-400"><i>i=1</i></span>
                                        </div>
                                        <i>p<sub>i</sub></i> log<sub>2</sub>(<i>p<sub>i</sub></i>)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOPSIS Math -->
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 shadow-sm">
                            <h4 class="text-fuchsia-600 font-semibold mb-4 text-sm uppercase tracking-wider">TOPSIS Ranking Distance</h4>
                            
                            <div class="mb-6">
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Euclidean Distance to Ideal Best</div>
                                <div class="bg-white border border-gray-200 rounded p-4 flex justify-center items-center font-serif text-2xl text-gray-800 tracking-wider shadow-inner">
                                    <div class="flex items-center gap-2">
                                        <i>D<sub>i</sub><sup>*</sup></i> = 
                                        <span class="text-4xl font-light">&radic;</span>
                                        <div class="border-t border-gray-400 pt-1 -ml-1 flex items-center gap-2"> 
                                            <div class="flex flex-col justify-center items-center relative mx-1">
                                                <span class="text-[10px] leading-none text-gray-400"><i>n</i></span>
                                                <span class="text-3xl leading-none">&sum;</span>
                                                <span class="text-[10px] leading-none text-gray-400"><i>j=1</i></span>
                                            </div>
                                            (<i>v<sub>ij</sub></i> - <i>v<sub>j</sub><sup>*</sup></i>)<sup>2</sup> 
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-2 text-center">Where <i>v<sub>ij</sub></i> is weighted normalized value, and <i>v<sub>j</sub><sup>*</sup></i> is ideal best value.</p>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Performance Score (Ranking)</div>
                                <div class="bg-white border border-gray-200 rounded p-4 flex justify-center items-center font-serif text-2xl text-gray-800 tracking-wider shadow-inner">
                                    <div class="flex items-center gap-2">
                                        <i>S<sub>i</sub></i> = 
                                        <div class="inline-flex flex-col items-center">
                                            <span class="border-b border-gray-400 px-2 pb-1"><i>D<sub>i</sub><sup>-</sup></i></span>
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
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
    diagnostics: Object
})

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.parsed.y.toFixed(2);
                }
            }
        }
    },
    scales: {
        y: { 
            min: 0.7, 
            max: 1.0,
            ticks: { font: { size: 10 } }
        },
        x: {
            ticks: { font: { size: 10 } },
            grid: { display: false }
        }
    }
}

const rfChartData = computed(() => ({
    labels: ['Epoch 1', 'Epoch 2', 'Epoch 3', 'Epoch 4', 'Epoch 5', 'Latest'],
    datasets: [{
        label: 'Accuracy',
        data: props.diagnostics.evaluation.random_forest.historical_accuracy,
        borderColor: '#059669', // emerald-600
        backgroundColor: 'rgba(5, 150, 105, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#059669',
        borderWidth: 2
    }]
}))

const topsisChartData = computed(() => ({
    labels: ['Epoch 1', 'Epoch 2', 'Epoch 3', 'Epoch 4', 'Epoch 5', 'Latest'],
    datasets: [{
        label: 'Correlation',
        data: props.diagnostics.evaluation.topsis.historical_correlation,
        borderColor: '#4f46e5', // indigo-600
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#4f46e5',
        borderWidth: 2
    }]
}))
</script>

<style>
@keyframes fadeInDown {
    from { opacity: 0; transform: translate3d(0, -20px, 0); }
    to { opacity: 1; transform: translate3d(0, 0, 0); }
}
.animate-fade-in-down { animation: fadeInDown 0.6s ease-out both; }

.custom-scrollbar-light::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar-light::-webkit-scrollbar-track {
    background: rgba(243, 244, 246, 1); /* gray-100 */
    border-radius: 8px;
}
.custom-scrollbar-light::-webkit-scrollbar-thumb {
    background: rgba(6, 182, 212, 0.4); /* cyan-500 */
    border-radius: 8px;
}
.custom-scrollbar-light::-webkit-scrollbar-thumb:hover {
    background: rgba(6, 182, 212, 0.8); 
}
</style>
